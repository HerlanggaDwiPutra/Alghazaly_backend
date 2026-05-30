<?php

/**
 * Suite Pengujian: Fitur Manajemen Galeri Album Sekolah (Admin Panel).
 *
 * Menguji integrasi pembuatan kantung penampung album dan mekanisme pertautan
 * relasinya terhadap item media individu (Many-to-Many).
 *
 * ATURAN BISNIS UTAMA:
 * - Admin mengendalikan status terbit (is_published) atas seluruh kerangka album.
 * - Kolom `slug` harus dapat dihasilkan (generate) otomatis berdasarkan atribut `title`.
 * - Pertautan/Sinkronisasi media dengan album (sync) menggantikan pertautan sebelumnya (Detach).
 */

use App\Models\Album;
use App\Models\Media;
use App\Models\User;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson, actingAs};

/**
 * Setup Global: Konfigurasi standar otorisasi pengguna ke dalam sistem API
 * menggunakan mock admin session agar pengunci (guard) terbuka.
 */
beforeEach(function () {
    $this->adminUser = createAdminUser();
});

/**
 * Skenario: Proteksi Endpoint Manajemen Album.
 * Prosedur: Akses data anonim.
 * Ekspektasi: API langsung melempar kegagalan auth 401 dan memblok akses.
 */
test('akses tanpa auth ditolak', function () {
    // Arrange: Tanpa Setup, tanpa token header.

    // Act: Hit ke rute koleksi album.
    $response = getJson('/api/admin/albums');

    // Assert: Sesuai harapan standar keamanan Sanctum.
    $response->assertStatus(401);
});

/**
 * Skenario: Tampilan Antrean Seluruh Entitas Album.
 * Prosedur: Mengakses semua galeri milik sekolah (tanpa filter publikasi).
 * Ekspektasi: Seluruh formasi data (array lengkap) muncul untuk kepentingan admin table panel.
 */
test('index menampilkan semua album', function () {
    // Arrange: Ciptakan 3 formasi album palsu.
    Album::factory()->count(3)->create();

    // Act: Hit GET dengan otorisasi admin.
    $response = actingAs($this->adminUser)->getJson('/api/admin/albums');

    // Assert: Semua item dipanggil tanpa kecuali.
    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(3);
});

/**
 * Skenario: Pembuatan Kerangka Album Baru.
 * Prosedur: Memberikan inputan payload minimal untuk perakitan album baru.
 * Ekspektasi: Album sukses mendarat ke DB, dilengkapi pembuatan mutasi format String to Slug.
 */
test('store album berhasil', function () {
    // Arrange: Cukup dengan persiapan payload bersih.
    
    // Act: Kirim pos request berisi properti judul dan visibilitas terbit.
    $response = actingAs($this->adminUser)->postJson('/api/admin/albums', [
        'title'        => 'Album Wisuda 2024',
        'description'  => 'Koleksi foto wisuda',
        'is_published' => true,
        'order'        => 1,
    ]);

    // Assert: Data memori persisten membuktikan mutasi auto slug dari form title berjalan optimal.
    $response->assertStatus(201);
    $this->assertDatabaseHas('albums', [
        'title' => 'Album Wisuda 2024',
        'slug'  => 'album-wisuda-2024',
    ]);
});

/**
 * Skenario: Proteksi Pencegahan Kerangka Album Kosong (Anonymous/No Title).
 * Prosedur: Admin sengaja menghapus judul pada form UI dan menyimpannya.
 * Ekspektasi: Validator turun tangan dan memaksa parameter title untuk disi (Wajib).
 */
test('store album validasi title wajib', function () {
    // Arrange: Menyiapkan body form bodong (tak utuh).
    
    // Act: Lempar permintaan pembuatan.
    $response = actingAs($this->adminUser)->postJson('/api/admin/albums', [
        'description' => 'Deskripsi',
    ]);

    // Assert: Kode 422 menggugurkan operasi penulisan basis data.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['title']);
});

/**
 * Skenario: Request Inspeksi Spesifik Album ID.
 * Prosedur: Pencomotan data album berdasar indeks ID di panel edit form.
 * Ekspektasi: Menerima rincian yang tepat dari single object album yang dituju.
 */
test('show album detail', function () {
    // Arrange: Album yang diincar.
    $album = Album::factory()->create();

    // Act: Tembakan ke endpoint terarah.
    $response = actingAs($this->adminUser)->getJson('/api/admin/albums/' . $album->album_id);

    // Assert: Identitas Primary Key matching.
    $response->assertStatus(200)
             ->assertJsonPath('album_id', $album->album_id);
});

/**
 * Skenario: Upaya Pencarian Pada Data Kosong (Hantu).
 * Prosedur: Meminta record album ID ke 9999.
 * Ekspektasi: Dihentikan oleh kontrol firstOrFail dan dibuang via status Not Found.
 */
test('show album tidak ada (404)', function () {
    // Act: Hit ke ruang hampa.
    $response = actingAs($this->adminUser)->getJson('/api/admin/albums/9999');

    // Assert: Standard 404 response.
    $response->assertStatus(404);
});

/**
 * Skenario: Revisi Pengubahan Judul Album (PATCH/PUT).
 * Prosedur: Penggantian nilai title album.
 * Ekspektasi: Field `title` maupun autogenerate `slug` pada database ikut berubah (Synchronized Update).
 */
test('update album berhasil', function () {
    // Arrange: Persiapan awal dengan title "Lama".
    $album = Album::factory()->create(['title' => 'Lama']);

    // Act: Mengubah objek tersebut secara mutlak (PUT) dengan value teranyar.
    $response = actingAs($this->adminUser)->putJson('/api/admin/albums/' . $album->album_id, [
        'title' => 'Baru',
    ]);

    // Assert: Update merubah kedua entitas kolom sekaligus secara koheren.
    $response->assertStatus(200);
    $this->assertDatabaseHas('albums', [
        'album_id' => $album->album_id,
        'title'    => 'Baru',
        'slug'     => 'baru',
    ]);
});

/**
 * Skenario: Mutasi Lanjutan URL Slug Saat Terjadi Revisi.
 * Prosedur: Mengganti parameter parsial.
 * Ekspektasi: Modifikasi judul wajib men-trigger perombakan ulang (regeneration) format slug.
 */
test('update album title memperbarui slug', function () {
    // Arrange: Entri purba "Judul Lama".
    $album = Album::factory()->create(['title' => 'Judul Lama', 'slug' => 'judul-lama']);

    // Act: Kirim payload modifikasi.
    actingAs($this->adminUser)->putJson('/api/admin/albums/' . $album->album_id, [
        'title' => 'Judul Baru Update',
    ]);

    // Assert: Slug di database ikut bersesuaian dengan entri terbarunya.
    $this->assertDatabaseHas('albums', [
        'album_id' => $album->album_id,
        'slug'     => 'judul-baru-update',
    ]);
});

/**
 * Skenario: Pemasukan Item Gambar (Media) ke Dalam Kerangka Album.
 * Prosedur: Melampirkan ID array media untuk album.
 * Ekspektasi: Tabel pivot `album_medias` bertugas mengakumulasi kaitan antar gambar terhadap album induk ini (Sync).
 */
test('update album sync medias', function () {
    // Arrange: Membuat 2 media yatim (Tak menempel di album manapun) lalu menyiapkan album kosong.
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $media1 = Media::factory()->create(['uploader_id' => $user->id]);
    $media2 = Media::factory()->create(['uploader_id' => $user->id]);

    // Act: Merekatkan kedua keping file statik ini ke dalam objek Album.
    $response = actingAs($this->adminUser)->putJson('/api/admin/albums/' . $album->album_id, [
        'medias' => [$media1->media_id, $media2->media_id],
    ]);

    // Assert: Penghitungan kardinalitas data berpotongan (relasi) pas dan match.
    $response->assertStatus(200);
    expect($album->fresh()->medias()->count())->toBe(2);
});

/**
 * Skenario: Penghapusan Penuh Instansi Album.
 * Prosedur: Menarik pelatuk pemusnahan (DELETE).
 * Ekspektasi: Memori tersapu bersih tanpa residu row tersisa.
 */
test('destroy album berhasil', function () {
    // Arrange: Dummy untuk dieksekusi hapus.
    $album = Album::factory()->create();

    // Act: Permintaan delete.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/albums/' . $album->album_id);

    // Assert: Row hilang.
    $response->assertStatus(200);
    $this->assertDatabaseMissing('albums', ['album_id' => $album->album_id]);
});

/**
 * Skenario: Penanganan Error Eksekusi Gagal Hapus Album.
 * Prosedur: Memberi ID sembarangan untuk dihabisi (9999).
 * Ekspektasi: Status hilang 404 tanpa fatal loop ke sistem exception layer.
 */
test('destroy album tidak ada (404)', function () {
    // Act: Hit target tak berjejak.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/albums/9999');

    // Assert: Sesuai rule routing controller laravel.
    $response->assertStatus(404);
});
