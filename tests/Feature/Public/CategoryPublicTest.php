<?php

/**
 * Suite Pengujian: Endpoint Publik Kategori Konten.
 *
 * Menguji bahwa endpoint kategori mengembalikan struktur hierarkis (parent-children)
 * yang digunakan oleh frontend untuk membangun navigasi dan filter konten.
 *
 * ATURAN BISNIS UTAMA:
 * - Endpoint hanya mengembalikan kategori root (parent_id = null) beserta children-nya.
 * - Kategori anak (subcategory) di-embed dalam array 'children' milik parent-nya,
 *   bukan sebagai item terpisah di level root response.
 */

use App\Models\Category;
use function Pest\Laravel\getJson;

/**
 * Skenario: Struktur Hierarki — Root Category dengan Children Tertanam.
 * Prosedur: Membuat satu kategori parent dan satu kategori anak yang merujuk ke parent.
 * Ekspektasi: Response hanya berisi 1 item (parent), dan item tersebut memiliki
 *             array 'children' yang berisi 1 item (kategori anak).
 */
test('index menampilkan root categories dengan children', function () {
    // Arrange: Membuat parent category (parent_id = null) dan child category yang merujuknya.
    //          Relasi parent-child ini membangun hierarki untuk navigasi konten.
    $parent = Category::factory()->create(['parent_id' => null]);
    Category::factory()->create(['parent_id' => $parent->category_id]);

    // Act: GET request ke endpoint daftar kategori.
    $response = getJson('/api/categories');

    // Assert: Hanya 1 item di level root, memiliki key 'children', dan children berisi 1 item.
    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(1);
    expect($data[0])->toHaveKey('children');
    expect(count($data[0]['children']))->toBe(1);
});

/**
 * Skenario: Response Kosong — Tidak Ada Kategori di Database.
 * Prosedur: Tidak membuat kategori apapun dan memanggil endpoint.
 * Ekspektasi: HTTP 200 dengan array kosong — bukan error 404 atau 500.
 */
test('index kosong jika belum ada data', function () {
    // Arrange: Tidak ada kategori yang dibuat (database bersih setelah RefreshDatabase).

    // Act: GET request ke endpoint daftar kategori.
    $response = getJson('/api/categories');

    // Assert: HTTP 200 dengan array kosong — endpoint aman walau tidak ada data.
    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(0);
});
