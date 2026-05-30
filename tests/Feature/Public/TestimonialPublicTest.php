<?php

/**
 * Suite Pengujian: Endpoint Publik Ulasan dan Testimonial.
 *
 * Memastikan bahwa testimonial alumni/orang tua yang ditampilkan pada halaman
 * depan sekolah disajikan dengan benar.
 *
 * ATURAN BISNIS UTAMA:
 * - Testimonial harus melalui tahapan moderasi, sehingga hanya yang berstatus
 *   `is_published = true` yang boleh diakses secara bebas.
 * - Testimonial diurutkan dengan aturan khusus (kolom `order`) untuk menyorot
 *   ulasan-ulasan terbaik atau yang dianggap representatif.
 */

use App\Models\Testimonial;
use function Pest\Laravel\getJson;

/**
 * Skenario: Testimonial Belum Dimoderasi (Draft) Tidak Ditampilkan.
 * Prosedur: Menginput ulasan yang sudah dipublikasi dan yang belum.
 * Ekspektasi: Hanya ulasan yang berstatus dipublikasi yang ditarik oleh API.
 */
test('index hanya tampilkan testimonial published', function () {
    // Arrange: Membuat ulasan dari pengunjung, satu lolos moderasi (published) dan satu masih draft.
    Testimonial::factory()->create(['is_published' => true]);
    Testimonial::factory()->create(['is_published' => false]);

    // Act: Mengirim GET request ke daftar ulasan publik.
    $response = getJson('/api/testimonials');

    // Assert: Endpoint hanya boleh mempublikasikan 1 ulasan.
    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(1);
});

/**
 * Skenario: Keamanan Ulasan — Hanya Menampilkan Ulasan Kosong Bila Semua Draft.
 * Prosedur: Memastikan endpoint bersih bila semua ulasan masih dalam antrean moderasi.
 * Ekspektasi: Pengembalian JSON array kosong.
 */
test('index tidak tampilkan testimonial unpublished', function () {
    // Arrange: Menyiapkan testimonial yang tidak berstatus dipublikasi.
    Testimonial::factory()->create(['is_published' => false]);

    // Act: Mengakses endpoint daftar testimonial publik.
    $response = getJson('/api/testimonials');

    // Assert: Mengembalikan 0 baris ulasan untuk keamanan data publik.
    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(0);
});

/**
 * Skenario: Penyorotan Ulasan Melalui Sistem Pengurutan Prioritas.
 * Prosedur: Membuat tiga ulasan dengan pengaturan 'order' (prioritas) secara acak.
 * Ekspektasi: Data wajib terurut berdasarkan kolom `order` agar ulasan prioritas berada di atas.
 */
test('index urut by order', function () {
    // Arrange: Membuat ulasan dengan bobot `order` yang telah diacak (3, 1, 2).
    Testimonial::factory()->create(['is_published' => true, 'order' => 3, 'name' => 'Testi C']);
    Testimonial::factory()->create(['is_published' => true, 'order' => 1, 'name' => 'Testi A']);
    Testimonial::factory()->create(['is_published' => true, 'order' => 2, 'name' => 'Testi B']);

    // Act: Melakukan request GET untuk mengambil seluruh data ulasan yang diurutkan.
    $response = getJson('/api/testimonials');

    // Assert: Endpoint harus mengatur barisan sesuai urutan dari terkecil ke terbesar secara konsisten.
    $response->assertStatus(200);
    $data = $response->json();
    expect($data[0]['name'])->toBe('Testi A');
    expect($data[1]['name'])->toBe('Testi B');
    expect($data[2]['name'])->toBe('Testi C');
});
