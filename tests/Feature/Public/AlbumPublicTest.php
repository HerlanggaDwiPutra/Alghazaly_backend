<?php

/**
 * Suite Pengujian: Endpoint Publik Galeri Foto (Album).
 *
 * Menguji bahwa galeri foto sekolah hanya menampilkan album yang sudah dipublikasikan
 * beserta koleksi media (foto) yang terhubung di dalamnya.
 *
 * ATURAN BISNIS UTAMA:
 * - Album dengan `is_published = false` tidak boleh tampil maupun dapat diakses via slug.
 * - Endpoint detail album harus menyertakan data `medias` (koleksi foto)
 *   yang terhubung melalui tabel pivot `album_medias`.
 */

use App\Models\Album;
use App\Models\Media;
use App\Models\User;
use function Pest\Laravel\getJson;

/**
 * Skenario: Keamanan Data — Album Unpublished Tidak Tampil di Index.
 * Prosedur: Membuat dua album (satu published, satu unpublished) dan memanggil index.
 * Ekspektasi: Hanya satu album yang dikembalikan — yang is_published = true.
 */
test('index hanya tampilkan album published', function () {
    // Arrange: Dua album dengan status publikasi berbeda.
    Album::factory()->create(['is_published' => true]);
    Album::factory()->create(['is_published' => false]);

    // Act: GET request ke endpoint galeri album publik.
    $response = getJson('/api/albums');

    // Assert: Hanya 1 album dalam response.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
});

/**
 * Skenario: Keamanan Data — Index Kosong Jika Semua Album Unpublished.
 * Prosedur: Membuat satu album unpublished dan memanggil index.
 * Ekspektasi: Array data kosong dikembalikan — tidak ada kebocoran data draft.
 */
test('index tidak tampilkan album unpublished', function () {
    // Arrange: Satu album dengan is_published = false.
    Album::factory()->create(['is_published' => false]);

    // Act: GET request ke endpoint galeri album publik.
    $response = getJson('/api/albums');

    // Assert: Data kosong — album unpublished disaring sepenuhnya.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(0);
});

/**
 * Skenario: Detail Album — Berhasil Diakses dengan Koleksi Media Terlampir.
 * Prosedur: Membuat album published, mengunggah satu media, dan menghubungkannya ke album.
 * Ekspektasi: HTTP 200 dengan slug yang benar dan array 'medias' berisi satu item.
 *             Ini memvalidasi bahwa eager loading relasi pivot album_medias berfungsi.
 */
test('show album by slug dengan medias', function () {
    // Arrange: Membuat album, media, lalu menghubungkan keduanya via tabel pivot album_medias.
    //          Parameter 'order' => 0 pada attach() mengisi kolom order di pivot.
    $user = User::factory()->create();
    $album = Album::factory()->create(['is_published' => true, 'slug' => 'galeri-wisuda']);
    $media = Media::factory()->create(['uploader_id' => $user->id]);
    $album->medias()->attach($media->media_id, ['order' => 0]);

    // Act: GET request ke endpoint detail album berdasarkan slug.
    $response = getJson('/api/albums/galeri-wisuda');

    // Assert: HTTP 200 dengan slug cocok dan array medias berisi tepat 1 item.
    $response->assertStatus(200)
             ->assertJsonPath('slug', 'galeri-wisuda');
    expect($response->json('medias'))->toHaveCount(1);
});

/**
 * Skenario: Detail Album — Slug Tidak Ditemukan (404).
 * Prosedur: Mengakses slug album yang tidak ada di database.
 * Ekspektasi: HTTP 404 — sistem tidak crash dengan error 500.
 */
test('show album slug tidak ada (404)', function () {
    // Arrange: Tidak ada album yang dibuat.

    // Act: GET request dengan slug yang tidak ada di database.
    $response = getJson('/api/albums/slug-tidak-ada');

    // Assert: HTTP 404.
    $response->assertStatus(404);
});

/**
 * Skenario: Keamanan Data — Album Draft Tidak Bisa Diakses via Slug (404).
 * Prosedur: Membuat album dengan is_published = false dan mencoba mengaksesnya via slug.
 * Ekspektasi: HTTP 404 — album unpublished tidak boleh bisa diakses langsung,
 *             bahkan jika slug-nya diketahui.
 */
test('show album unpublished tidak bisa diakses (404)', function () {
    // Arrange: Album dengan is_published = false — belum siap dipublikasikan ke publik.
    Album::factory()->create(['is_published' => false, 'slug' => 'album-draft']);

    // Act: Mencoba mengakses album draft menggunakan slug-nya.
    $response = getJson('/api/albums/album-draft');

    // Assert: HTTP 404 — album draft disembunyikan dari akses publik.
    $response->assertStatus(404);
});
