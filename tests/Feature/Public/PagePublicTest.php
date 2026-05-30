<?php

/**
 * Suite Pengujian: Endpoint Publik Halaman Statis (Pages).
 *
 * Menguji fungsionalitas sistem halaman statis (seperti "Tentang Kami", "Visi Misi")
 * yang dapat diakses oleh publik tanpa autentikasi.
 *
 * ATURAN BISNIS UTAMA:
 * - Hanya halaman dengan status `is_published = true` yang boleh diakses.
 * - Halaman berstatus draft (is_published = false) harus membalikkan respons 404 (Not Found).
 * - Pengurutan halaman pada respons indeks ditentukan oleh kolom `order` untuk
 *   menyusun hierarki menu navigasi.
 */

use App\Models\Page;
use function Pest\Laravel\getJson;

/**
 * Skenario: Keamanan Data — Halaman Draft Tidak Muncul di Indeks.
 * Prosedur: Membuat dua halaman (satu terpublikasi, satu draft) dan memanggil list.
 * Ekspektasi: Hanya halaman yang terpublikasi yang dikembalikan oleh API.
 */
test('index hanya tampilkan halaman published', function () {
    // Arrange: Membuat dua halaman dengan status publikasi berbeda.
    Page::factory()->create(['is_published' => true, 'order' => 1]);
    Page::factory()->create(['is_published' => false, 'order' => 2]);

    // Act: Melakukan HTTP GET request ke daftar halaman publik.
    $response = getJson('/api/pages');

    // Assert: Memastikan hanya 1 halaman yang terpublikasi (is_published = true) yang ada di response.
    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(1);
});

/**
 * Skenario: Keamanan Data — Indeks Kosong Jika Semua Halaman Adalah Draft.
 * Prosedur: Membuat satu halaman dengan status draft dan memastikan endpoint memfilter dengan benar.
 * Ekspektasi: Respons harus berupa list kosong tanpa kebocoran data draft.
 */
test('index tidak tampilkan halaman draft', function () {
    // Arrange: Membuat satu halaman yang masih dalam status draft (is_published = false).
    Page::factory()->create(['is_published' => false, 'title' => 'Draft Page']);

    // Act: Memanggil endpoint list halaman publik.
    $response = getJson('/api/pages');

    // Assert: Endpoint mengembalikan array kosong, membuktikan filter status efektif.
    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(0);
});

/**
 * Skenario: Akses Halaman Terpublikasi Berdasarkan Slug.
 * Prosedur: Membuat sebuah halaman yang terpublikasi dan mengaksesnya secara spesifik via slug.
 * Ekspektasi: Respons berisi data lengkap halaman tersebut yang cocok dengan slug yang diberikan.
 */
test('show halaman by slug', function () {
    // Arrange: Membuat halaman yang terpublikasi dengan slug spesifik.
    $page = Page::factory()->create(['is_published' => true, 'slug' => 'tentang-kami']);

    // Act: Mengakses endpoint detail halaman berdasarkan slug unik.
    $response = getJson('/api/pages/tentang-kami');

    // Assert: Status 200 OK dan slug pada respons sesuai dengan yang diminta.
    $response->assertStatus(200)
             ->assertJsonPath('slug', 'tentang-kami');
});

/**
 * Skenario: Penanganan Halaman Tidak Ditemukan.
 * Prosedur: Mengakses endpoint detail halaman dengan slug yang tidak ada di database.
 * Ekspektasi: Sistem melempar status 404 (Not Found) dengan anggun tanpa error fatal.
 */
test('show halaman slug tidak ada (404)', function () {
    // Arrange: Tanpa penyiapan data (database kosong).

    // Act: Mengakses slug yang tidak valid.
    $response = getJson('/api/pages/slug-tidak-ada');

    // Assert: Status HTTP 404 terkonfirmasi.
    $response->assertStatus(404);
});

/**
 * Skenario: Pencegahan Akses Langsung ke Halaman Draft.
 * Prosedur: Membuat halaman dengan is_published = false, lalu mencoba memanggilnya via slug.
 * Ekspektasi: Halaman tidak bisa diakses dan sistem mengembalikan 404 seolah-olah halaman tidak pernah ada.
 */
test('show halaman draft tidak bisa diakses (404)', function () {
    // Arrange: Membuat halaman dengan slug valid tetapi belum dipublikasikan.
    Page::factory()->create(['is_published' => false, 'slug' => 'halaman-draft']);

    // Act: Mencoba memaksa akses ke slug halaman draft secara langsung.
    $response = getJson('/api/pages/halaman-draft');

    // Assert: Akses ditolak dan menghasilkan 404 untuk menjaga kerahasiaan konten draf.
    $response->assertStatus(404);
});
