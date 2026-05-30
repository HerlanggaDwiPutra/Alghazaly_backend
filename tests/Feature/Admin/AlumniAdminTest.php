<?php

/**
 * Suite Pengujian: Fitur Manajemen Data Alumni (Admin Panel).
 *
 * Menguji kelancaran siklus CRUD terhadap buku profil tahunan (Alumni) sekolah.
 *
 * ATURAN BISNIS UTAMA:
 * - Visibilitas data ke frontend publik diatur oleh flag `is_published`.
 * - Parameter profil dibatasi dengan validasi field seperti panjang standar 4 digit
 *   untuk `graduation_year` yang divalidasi dengan ketat.
 */

use App\Models\Alumni;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson, actingAs};

/**
 * Setup Global: Konfigurasi default menyematkan hak admin.
 */
beforeEach(function () {
    $this->adminUser = createAdminUser();
});

/**
 * Skenario: Konfirmasi Restriksi Pengunjung Non-Admin.
 * Prosedur: Membaca entri rute tanpa token identitas.
 * Ekspektasi: API sekuritas (Sanctum) menyetop proses di layer middleware (401).
 */
test('akses tanpa auth ditolak', function () {
    // Act: Hit ke direktori root resource alumni admin.
    $response = getJson('/api/admin/alumni');

    // Assert: Sukses dicekal.
    $response->assertStatus(401);
});

/**
 * Skenario: Tampilan Antrean Entitas Tabel Database Alumni.
 * Prosedur: Melakukan request panggul ke indeks rute alumni.
 * Ekspektasi: Laravel Resource mengembalikan struktur array JSON pagination.
 */
test('index menampilkan semua alumni (paginated)', function () {
    // Arrange: Persiapkan 3 data untuk trigger paginasi.
    Alumni::factory()->count(3)->create();

    // Act: Pengambilan dari endpoint.
    $response = actingAs($this->adminUser)->getJson('/api/admin/alumni');

    // Assert: Sesuai harapan standar paginasi framework (Memuat informasi halaman, limitasi dsb).
    $response->assertStatus(200)
             ->assertJsonStructure(['current_page', 'data', 'per_page', 'total']);
});

/**
 * Skenario: Implementasi Saringan Daftar Tabel Tahun Angkatan.
 * Prosedur: Admin membidik query string dengan parameter ?year=2023.
 * Ekspektasi: Barisan angkatan 2024 dll tidak bocor ke hasil. Hanya angkatan 2023.
 */
test('index filter by graduation year', function () {
    // Arrange: Berikan dataset dari 2 rentang waktu berlainan.
    Alumni::factory()->create(['graduation_year' => 2023]);
    Alumni::factory()->create(['graduation_year' => 2024]);

    // Act: Membubuhkan pencarian query spesifik 2023.
    $response = actingAs($this->adminUser)->getJson('/api/admin/alumni?year=2023');

    // Assert: Pembersihan dan penyaringan sukses tanpa cacat algoritma.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['graduation_year'])->toBe(2023);
});

/**
 * Skenario: Pembuatan Lembar Profil Mandiri Alumni (Happy Path).
 * Prosedur: Mempost payload json lengkap (nama, tahun, status).
 * Ekspektasi: Input tersimpan tepat di MySQL backend.
 */
test('store alumni berhasil', function () {
    // Arrange: Kosong.

    // Act: Form diisikan oleh admin via HTTP POST.
    $response = actingAs($this->adminUser)->postJson('/api/admin/alumni', [
        'name'            => 'Ahmad Fauzi',
        'graduation_year' => 2023,
        'is_published'    => true,
    ]);

    // Assert: Verifikasi balasan API Created 201 dan pencatatan nama ada.
    $response->assertStatus(201);
    $this->assertDatabaseHas('alumni', ['name' => 'Ahmad Fauzi']);
});

/**
 * Skenario: Aturan Ketat Pendaftaran (Validasi Null Nama).
 * Prosedur: Pengunggahan form dibiarkan dengan string 'name' bolong.
 * Ekspektasi: Sistem harus memotong niat admin sebelum memadati DB dengan akun no-name (hantu).
 */
test('store alumni validasi name wajib', function () {
    // Arrange: Kosong.

    // Act: Payload sengaja meniadakan properti penting.
    $response = actingAs($this->adminUser)->postJson('/api/admin/alumni', [
        'graduation_year' => 2023,
    ]);

    // Assert: Tanggapan dari class Validator menembakkan 422.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name']);
});

/**
 * Skenario: Filter Atribut Tahun Angkatan Tidak Standar.
 * Prosedur: Mengumpankan input tahun ganjil berupa integer 2-digit (99).
 * Ekspektasi: Rule validator (mungkin integer constraint atau regex pattern) harus menjegalnya.
 */
test('store alumni validasi graduation_year 4 digit', function () {
    // Arrange: Kosong.

    // Act: Menerobos masuk 2 digit tahun agar membingungkan sistem pengelompokkan query.
    $response = actingAs($this->adminUser)->postJson('/api/admin/alumni', [
        'name'            => 'Test',
        'graduation_year' => 99,
    ]);

    // Assert: Tembok pertahanan validasi menginstruksikan format empat digit YYYY (20xx).
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['graduation_year']);
});

/**
 * Skenario: Edit Profil Rekaman Lawas.
 * Prosedur: Meniupkan verb PUT (Update absolut) ke data spesifik.
 * Ekspektasi: Catatan baris MySQL berganti wujud.
 */
test('update alumni berhasil', function () {
    // Arrange: Rekam satu dummy "Nama Lama".
    $alumni = Alumni::factory()->create(['name' => 'Nama Lama']);

    // Act: Tindakan admin menggantinya dengan "Nama Baru".
    $response = actingAs($this->adminUser)->putJson('/api/admin/alumni/' . $alumni->alumni_id, [
        'name' => 'Nama Baru',
    ]);

    // Assert: Terjamin sinkron dengan skema DB.
    $response->assertStatus(200);
    $this->assertDatabaseHas('alumni', ['alumni_id' => $alumni->alumni_id, 'name' => 'Nama Baru']);
});

/**
 * Skenario: Evakuasi / Hapus Paksa Baris DB.
 * Prosedur: Admin menyuruh API menghapus ID.
 * Ekspektasi: Eksekusi beres dan DB membersihkan diri.
 */
test('destroy alumni berhasil', function () {
    // Arrange: Profil fiktif siap hapus.
    $alumni = Alumni::factory()->create();

    // Act: Menembakkan meriam metode DELETE.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/alumni/' . $alumni->alumni_id);

    // Assert: Hasil pemindaian pasca-tembakan tidak mendeteksi ID tersebut lagi.
    $response->assertStatus(200);
    $this->assertDatabaseMissing('alumni', ['alumni_id' => $alumni->alumni_id]);
});

/**
 * Skenario: Respon Ketiadaan Entitas Hapus.
 * Prosedur: Mencabut akar profil dari baris index yang fana/kosong (9999).
 * Ekspektasi: Exception tak meluap, API ramah menjawab 404 (Objek tidak ada).
 */
test('destroy alumni tidak ada (404)', function () {
    // Act: Mengeksekusi delete ke endpoint ghost ID.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/alumni/9999');

    // Assert: Sistem menghandlenya sebagai record Not Found.
    $response->assertStatus(404);
});
