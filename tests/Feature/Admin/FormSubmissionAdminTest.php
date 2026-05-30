<?php

/**
 * Suite Pengujian: Fitur Manajemen Kotak Masuk / Pesan Submisi (Admin Panel).
 *
 * Menguji integrasi sistem formulir dinamis bagian pengelolaan antrean pesan.
 * Memastikan admin dapat memantau pesan pengunjung yang masuk berdasarkan form origin
 * serta menandainya sebagai 'read' secara otomatis ketika dibuka.
 *
 * ATURAN BISNIS UTAMA:
 * - List submission bisa disaring dengan 'form_id' untuk memilah tipe formulir masuk.
 * - Pembacaan detail pesan (show) secara otomatis dan instan akan menggeser flag
 *   `is_read` dari false menjadi true di database.
 */

use App\Models\Form;
use App\Models\FormSubmission;
use function Pest\Laravel\{getJson, deleteJson, actingAs};

/**
 * Setup Global: Pemasangan bypass sesi otoritas administrator.
 */
beforeEach(function () {
    $this->adminUser = createAdminUser();
});

/**
 * Skenario: Konfirmasi Pembatasan Akses Telinga Luar.
 * Prosedur: Menembak API inbox form tanpa login.
 * Ekspektasi: Permintaan harus dipatahkan dengan Unauthenticated 401.
 */
test('akses tanpa auth ditolak', function () {
    // Act: Menerobos masuk jalur API pesan.
    $response = getJson('/api/admin/messages');

    // Assert: Sukses diblokir pelindung Sanctum.
    $response->assertStatus(401);
});

/**
 * Skenario: Tampilan Indeks Seluruh Tumpukan Pesan.
 * Prosedur: Mengambil koleksi dari kotak masuk sekolah secara general.
 * Ekspektasi: Menyediakan format pagination dengan meta-data yang lengkap.
 */
test('index menampilkan semua pesan (paginated)', function () {
    // Arrange: Ciptakan 3 surat dari pengunjung di database.
    FormSubmission::factory()->count(3)->create();

    // Act: Lakukan request oleh user otorisasi admin.
    $response = actingAs($this->adminUser)->getJson('/api/admin/messages');

    // Assert: Komposisi respon Paginasi Laravel valid dan tak error.
    $response->assertStatus(200)
             ->assertJsonStructure(['current_page', 'data', 'per_page', 'total']);
});

/**
 * Skenario: Mode Filter Pemilahan Pesan Berdasarkan Jenis Form.
 * Prosedur: Memisahkan pesan masuk form "Buku Tamu" dengan pesan "Laporan Aduan".
 * Ekspektasi: Penggunaan parameter '?form_id' mereduksi tabel tepat pada origin form terkait.
 */
test('index filter by form_id', function () {
    // Arrange: Buat 2 Form berbeda dan isikan masing-masing 1 pesan submission.
    $form1 = Form::factory()->create();
    $form2 = Form::factory()->create();
    FormSubmission::factory()->create(['form_id' => $form1->form_id]);
    FormSubmission::factory()->create(['form_id' => $form2->form_id]);

    // Act: Hit target khusus hanya untuk melirik form_id pertama.
    $response = actingAs($this->adminUser)->getJson('/api/admin/messages?form_id=' . $form1->form_id);

    // Assert: Saringan aman, sisa array pas bernilai 1.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
});

/**
 * Skenario: Mode Filter Status Keterbacaan (Read / Unread).
 * Prosedur: Admin menyortir kotak masuk untuk mencari pesan yang belum sempat dibaca.
 * Ekspektasi: Menggantung flag parameter is_read bernilai boolean nol.
 */
test('index filter by is_read', function () {
    // Arrange: 1 Pesan terbaca dan 1 pesan murni belum terbaca.
    FormSubmission::factory()->create(['is_read' => false]);
    FormSubmission::factory()->create(['is_read' => true]);

    // Act: Set filter `is_read=0` (false).
    $response = actingAs($this->adminUser)->getJson('/api/admin/messages?is_read=0');

    // Assert: Data kotor yg sudah dibaca disingkirkan.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
});

/**
 * Skenario: Transformasi Status Otomatis Saat Membuka Pesan.
 * Prosedur: Admin melakukan klik ke detail bacaan pesan (Aksi GET rute spesifik id).
 * Ekspektasi: Di balik layar API tidak cuma melakukan aksi 'read', melainkan update mutasi (patching) `is_read` ke true.
 */
test('show pesan detail dan otomatis tandai sudah dibaca', function () {
    // Arrange: Pesan perawan dengan status unread/belum dibaca.
    $submission = FormSubmission::factory()->create(['is_read' => false]);

    // Act: Admin mengakses request detail pesan.
    $response = actingAs($this->adminUser)->getJson('/api/admin/messages/' . $submission->submission_id);

    // Assert: Konten merespon dengan OK 200...
    $response->assertStatus(200)
             ->assertJsonPath('submission_id', $submission->submission_id);

    // Assert (Internal Check): Tabel mendeteksi mutasi update dari false menjadi true.
    $this->assertDatabaseHas('form_submissions', [
        'submission_id' => $submission->submission_id,
        'is_read'       => true,
    ]);
});

/**
 * Skenario: Penanganan Pembacaan Tumpukan Pesan Nihil.
 * Prosedur: Pencarian manual id form submisi asalan.
 * Ekspektasi: Respon ditangani 404 tanpa menyebabkan exception fatal di internal log.
 */
test('show pesan tidak ada (404)', function () {
    // Act: Buka pesan index ke 9999.
    $response = actingAs($this->adminUser)->getJson('/api/admin/messages/9999');

    // Assert: Rute dilindungi 404 error default.
    $response->assertStatus(404);
});

/**
 * Skenario: Pembuangan Data / Tong Sampah Submisi.
 * Prosedur: Admin menghapus surat masuk yang tidak berguna (Spam dsb).
 * Ekspektasi: Sukses melenyapkan record dari memori penyimpanan SQL.
 */
test('destroy pesan berhasil', function () {
    // Arrange: Berikan satu surat random ke database.
    $submission = FormSubmission::factory()->create();

    // Act: Suruh controller menghapusnya secara deterministik.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/messages/' . $submission->submission_id);

    // Assert: Hasilnya, tabel form_submissions kehilangan record id tersebut.
    $response->assertStatus(200);
    $this->assertDatabaseMissing('form_submissions', ['submission_id' => $submission->submission_id]);
});

/**
 * Skenario: Pembuangan Target Fiktif.
 * Prosedur: Men-delete record indeks kosong.
 * Ekspektasi: Respon ditangani aman.
 */
test('destroy pesan tidak ada (404)', function () {
    // Act: Paksaan delete.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/messages/9999');

    // Assert: API membalikkan error not found.
    $response->assertStatus(404);
});
