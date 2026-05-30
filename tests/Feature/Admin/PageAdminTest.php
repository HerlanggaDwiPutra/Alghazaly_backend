<?php

/**
 * Suite Pengujian: Fitur Manajemen Halaman Statis (Admin Panel).
 *
 * Menguji administrasi struktur dasar halaman situs (Visi Misi, Sejarah, dsb).
 *
 * ATURAN BISNIS UTAMA:
 * - Admin mampu menciptakan halaman (Page) dengan judul (`title`) dan `content`.
 * - String auto-slug akan digenerate dinamis secara otomatis oleh sistem
 *   berdasarkan form inputan properti 'title'.
 */

use App\Models\Page;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson, actingAs};

/**
 * Setup Global: Melimpahkan hak akses sesi testing ke pengguna level admin
 * secara simultan bagi seluruh pengujian baris-baris ke bawah.
 */
beforeEach(function () {
    $this->adminUser = createAdminUser();
});

/**
 * Skenario: Konfirmasi Restriksi Pengunjung Non-Admin.
 * Prosedur: Membaca entri URL index Pages secara anonim.
 * Ekspektasi: Dihentikan paksa (401) oleh middleware pertahanan endpoint.
 */
test('akses tanpa auth ditolak', function () {
    // Act: Menerobos masuk gerbang Pages.
    $response = getJson('/api/admin/pages');

    // Assert: Sukses diblokir pelindung Auth.
    $response->assertStatus(401);
});

/**
 * Skenario: Tampilan Antrean Seluruh Entitas Halaman.
 * Prosedur: Admin melakukan GET request ke rute Pages indeks.
 * Ekspektasi: Menarik seluruh array rekaman halaman tanpa ada yang terpotong filter status terbit.
 */
test('index menampilkan semua halaman', function () {
    // Arrange: Buat 3 lembar halaman simulasi ke DB.
    Page::factory()->count(3)->create();

    // Act: Pengambilan total dari dashboard admin.
    $response = actingAs($this->adminUser)->getJson('/api/admin/pages');

    // Assert: Ketiga page harus termuat dalam respon balikan.
    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(3);
});

/**
 * Skenario: Perakitan Halaman CMS Baru (Happy Path).
 * Prosedur: Menyematkan Judul serta Konten artikel halaman via Payload POST.
 * Ekspektasi: Laravel menterjemahkannya ke MySQL row, dengan bantuan mutasi Str::slug() di background.
 */
test('store halaman berhasil', function () {
    // Act: Membubuhkan field minimal yang divalidasi.
    $response = actingAs($this->adminUser)->postJson('/api/admin/pages', [
        'title'   => 'Tentang Kami',
        'content' => 'Isi halaman tentang kami.',
    ]);

    // Assert: Tersimpan sukses (201 Created), membuktikan konversi auto-slug untuk parameter URL berfungsi ("tentang-kami").
    $response->assertStatus(201);
    $this->assertDatabaseHas('pages', [
        'title' => 'Tentang Kami',
        'slug'  => 'tentang-kami',
    ]);
});

/**
 * Skenario: Penolakan Rekayasa Halaman Tak Bernama.
 * Prosedur: Admin lupa/sengaja mengabaikan title box pada form dashboard.
 * Ekspektasi: Menghasilkan balikan 422 agar sistem tak bingung dalam men-generate field auto slug.
 */
test('store halaman validasi title wajib', function () {
    // Act: Meloloskan form dengan teks tubuh namun judul kosong.
    $response = actingAs($this->adminUser)->postJson('/api/admin/pages', [
        'content' => 'Isi konten',
    ]);

    // Assert: Digagalkan dan divonis oleh class Validator Request.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['title']);
});

/**
 * Skenario: Penolakan Publikasi Kosong Tanpa Esensi.
 * Prosedur: Mengecoh validator dengan title lengkap tetapi badan konten rumpang (null).
 * Ekspektasi: Validasi required pada field 'content' mengagalkannya.
 */
test('store halaman validasi content wajib', function () {
    // Act: Mencoba menerbitkan page hanya dengan judul semata.
    $response = actingAs($this->adminUser)->postJson('/api/admin/pages', [
        'title' => 'Test Page',
    ]);

    // Assert: Pengecualian Validator muncul pada flag 'content'.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['content']);
});

/**
 * Skenario: Pemeriksaan Elemen Visual Parsial (Show Node).
 * Prosedur: Meneliti isian detail properti dari 1 lembar halaman terpilih.
 * Ekspektasi: Pengembalian JSON mencocokkan id kuncian baris DB terlampir.
 */
test('show halaman detail', function () {
    // Arrange: Persiapkan 1 target simulasi.
    $page = Page::factory()->create();

    // Act: Cari detail form spesifiknya.
    $response = actingAs($this->adminUser)->getJson('/api/admin/pages/' . $page->page_id);

    // Assert: Sesuai harapan id (Match).
    $response->assertStatus(200)
             ->assertJsonPath('page_id', $page->page_id);
});

/**
 * Skenario: Pencegahan Error Fatal Pada Missing Detail.
 * Prosedur: Meminta record di luar cakupan kapasitas rekaman row sistem (Id 9999).
 * Ekspektasi: Merespon ringan dengan penolakan Not Found (404) standar framework.
 */
test('show halaman tidak ada (404)', function () {
    // Act: Hit target tak berjejak.
    $response = actingAs($this->adminUser)->getJson('/api/admin/pages/9999');

    // Assert: Tanggapan ramah API Restful.
    $response->assertStatus(404);
});

/**
 * Skenario: Mutasi Pengubahan Judul Utama.
 * Prosedur: Admin memperbaiki/merevisi nilai label parameter title.
 * Ekspektasi: Atribut judul direkam ke nilai teranyar pada memori server (Update/PUT).
 */
test('update halaman berhasil', function () {
    // Arrange: Lembar usang "Lama".
    $page = Page::factory()->create(['title' => 'Lama']);

    // Act: Hit form edit dengan teks injeksi baru.
    $response = actingAs($this->adminUser)->putJson('/api/admin/pages/' . $page->page_id, [
        'title' => 'Baru',
    ]);

    // Assert: Sinkronisasi pembaruan MySQL mutlak sukses.
    $response->assertStatus(200);
    $this->assertDatabaseHas('pages', ['page_id' => $page->page_id, 'title' => 'Baru', 'slug' => 'baru']);
});

/**
 * Skenario: Regenerasi Tautan Parametrik (Slug Update Sync).
 * Prosedur: Saat mengubah title, bagaimana tanggapan string URL permanen?
 * Ekspektasi: Observer model/update event memastikan link statis slug dire-format/di-generate ulang
 *             mencerminkan perubahaan judul terbaru (Misal agar SEO tetap relevan).
 */
test('update halaman title memperbarui slug', function () {
    // Arrange: Skenario URL purba.
    $page = Page::factory()->create(['title' => 'Old Title', 'slug' => 'old-title']);

    // Act: Kirim payload nama yang totally different.
    actingAs($this->adminUser)->putJson('/api/admin/pages/' . $page->page_id, [
        'title' => 'New Title Update',
    ]);

    // Assert: Database dipaksa mengamini format konversi judul ke slug strip (Kebab Case) baru secara simultan.
    $this->assertDatabaseHas('pages', ['page_id' => $page->page_id, 'slug' => 'new-title-update']);
});

/**
 * Skenario: Evakuasi Baris Data Penuh (Hard Delete).
 * Prosedur: Melontarkan verb DELETE ke record database aktif.
 * Ekspektasi: Penggunaan query un-link menghapusnya bersih dari index MySql tabel.
 */
test('destroy halaman berhasil', function () {
    // Arrange: Lembar acak untuk dihancurkan.
    $page = Page::factory()->create();

    // Act: Permintaan delete spesifik.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/pages/' . $page->page_id);

    // Assert: Row hilang selamanya.
    $response->assertStatus(200);
    $this->assertDatabaseMissing('pages', ['page_id' => $page->page_id]);
});
