<?php

/**
 * Suite Pengujian: Endpoint Publik Artikel/Berita Sekolah.
 *
 * Menguji bahwa portal berita sekolah hanya menampilkan artikel yang sudah dipublikasikan
 * dan mendukung filter/pencarian sesuai kebutuhan pengunjung halaman.
 *
 * ATURAN BISNIS UTAMA:
 * - Artikel dengan status 'draft' atau 'archived' TIDAK boleh tampil di endpoint publik.
 * - Pengurutan berdasarkan `published_at` (bukan `created_at`) memastikan artikel
 *   yang dijadwalkan tampil sesuai urutan tanggal publikasi aktualnya.
 * - Filter kategori menggunakan slug (bukan ID) agar URL tetap SEO-friendly.
 */

use App\Models\Post;
use App\Models\User;
use App\Models\Category;
use function Pest\Laravel\getJson;

/**
 * Skenario: Keamanan Data — Artikel Draft Tidak Bocor ke Endpoint Publik.
 * Prosedur: Membuat dua artikel (satu published, satu draft) dan memanggil index.
 * Ekspektasi: Hanya satu artikel yang dikembalikan, membuktikan filter status aktif.
 */
test('index hanya tampilkan post published', function () {
    // Arrange: Membuat dua artikel dengan status berbeda untuk memvalidasi filter.
    //          'published_at' wajib diisi pada artikel yang dipublikasikan.
    $user = User::factory()->create();
    Post::factory()->create(['author_id' => $user->id, 'status' => 'published', 'published_at' => now()]);
    Post::factory()->create(['author_id' => $user->id, 'status' => 'draft']);

    // Act: GET request ke endpoint indeks artikel publik.
    $response = getJson('/api/posts');

    // Assert: Hanya 1 artikel dalam response, dan statusnya 'published'.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['status'])->toBe('published');
});

/**
 * Skenario: Filter Kategori — Artikel yang Ditampilkan Sesuai Slug Kategori.
 * Prosedur: Membuat dua artikel published, tetapi hanya satu yang terhubung ke kategori 'teknologi'.
 * Ekspektasi: Filter `?category=teknologi` hanya mengembalikan artikel dari kategori tersebut.
 */
test('index filter by category slug', function () {
    // Arrange: Membuat kategori, dua artikel, lalu menghubungkan satu artikel ke kategori.
    //          Relasi many-to-many dilakukan via attach() pada tabel pivot post_categories.
    $user = User::factory()->create();
    $category = Category::factory()->create(['slug' => 'teknologi']);
    $post = Post::factory()->create(['author_id' => $user->id, 'status' => 'published', 'published_at' => now()]);
    $post->categories()->attach($category->category_id);

    Post::factory()->create(['author_id' => $user->id, 'status' => 'published', 'published_at' => now()]);

    // Act: Memanggil endpoint dengan query string filter kategori berdasarkan slug.
    $response = getJson('/api/posts?category=teknologi');

    // Assert: Hanya 1 artikel yang dikembalikan — yang terhubung ke kategori 'teknologi'.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
});

/**
 * Skenario: Pencarian Judul — Filter Berdasarkan Kata Kunci.
 * Prosedur: Membuat dua artikel dengan judul berbeda dan mencari dengan kata kunci 'Laravel'.
 * Ekspektasi: Hanya artikel dengan judul yang mengandung 'Laravel' yang dikembalikan.
 */
test('index filter by search', function () {
    // Arrange: Membuat dua artikel published dengan judul yang kontras.
    $user = User::factory()->create();
    Post::factory()->create(['author_id' => $user->id, 'title' => 'Belajar Laravel', 'status' => 'published', 'published_at' => now()]);
    Post::factory()->create(['author_id' => $user->id, 'title' => 'Tutorial React', 'status' => 'published', 'published_at' => now()]);

    // Act: GET request dengan filter pencarian judul.
    $response = getJson('/api/posts?search=Laravel');

    // Assert: Hanya satu artikel dikembalikan dengan judul yang cocok.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['title'])->toBe('Belajar Laravel');
});

/**
 * Skenario: Pengurutan — Artikel Terbaru Berdasarkan `published_at` (Descending).
 * Prosedur: Membuat dua artikel dengan tanggal publikasi berbeda.
 * Ekspektasi: Artikel yang lebih baru tampil di posisi pertama.
 *             Ini memvalidasi bahwa portal berita menggunakan `published_at`, bukan `created_at`.
 */
test('index urut by published_at desc', function () {
    // Arrange: Artikel pertama dipublikasikan 2 hari yang lalu, artikel kedua baru saja.
    $user = User::factory()->create();
    Post::factory()->create(['author_id' => $user->id, 'status' => 'published', 'published_at' => now()->subDays(2)]);
    Post::factory()->create(['author_id' => $user->id, 'status' => 'published', 'published_at' => now()]);

    // Act: GET request ke endpoint indeks artikel.
    $response = getJson('/api/posts');

    // Assert: Artikel pertama di response adalah yang paling baru (published_at terbesar).
    $response->assertStatus(200);
    $data = $response->json('data');
    // First should be the newest
    expect($data[0]['published_at'] >= $data[1]['published_at'])->toBeTrue();
});

/**
 * Skenario: Detail Artikel — Berhasil Diakses Menggunakan Slug.
 * Prosedur: Mengakses detail artikel melalui slug-nya yang unik.
 * Ekspektasi: HTTP 200 dengan data artikel yang sesuai slug yang diminta.
 */
test('show post by slug', function () {
    // Arrange: Membuat artikel published dengan slug yang spesifik.
    $user = User::factory()->create();
    Post::factory()->create(['author_id' => $user->id, 'slug' => 'post-pertama', 'status' => 'published', 'published_at' => now()]);

    // Act: GET request ke endpoint detail artikel berdasarkan slug.
    $response = getJson('/api/posts/post-pertama');

    // Assert: HTTP 200 dengan slug yang cocok dalam response.
    $response->assertStatus(200)
             ->assertJsonPath('slug', 'post-pertama');
});

/**
 * Skenario: Detail Artikel — Slug Tidak Ditemukan (404).
 * Prosedur: Mengakses slug artikel yang tidak ada di database.
 * Ekspektasi: HTTP 404 — bukan error 500 yang mengekspos stack trace.
 */
test('show post slug tidak ada (404)', function () {
    // Arrange: Tidak ada artikel yang dibuat.

    // Act: GET request dengan slug yang tidak valid.
    $response = getJson('/api/posts/slug-tidak-ada');

    // Assert: HTTP 404 — firstOrFail() di controller melempar ModelNotFoundException.
    $response->assertStatus(404);
});

/**
 * Skenario: Keamanan Data — Artikel Draft Tidak Bisa Diakses via Slug (404).
 * Prosedur: Membuat artikel dengan status 'draft' dan mencoba mengaksesnya via slug.
 * Ekspektasi: HTTP 404 — artikel draft harus disembunyikan dari akses publik langsung,
 *             bahkan jika slug-nya diketahui.
 */
test('show post draft tidak bisa diakses (404)', function () {
    // Arrange: Membuat artikel dengan status 'draft' — tidak dipublikasikan.
    $user = User::factory()->create();
    Post::factory()->create(['author_id' => $user->id, 'slug' => 'post-draft', 'status' => 'draft']);

    // Act: Mencoba mengakses artikel draft menggunakan slug-nya secara langsung.
    $response = getJson('/api/posts/post-draft');

    // Assert: HTTP 404 — artikel draft tidak boleh bisa diakses meski slug-nya benar.
    $response->assertStatus(404);
});
