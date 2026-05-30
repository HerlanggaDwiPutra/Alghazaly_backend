<?php

/**
 * Suite Pengujian: Endpoint Publik Pengaturan Sistem (Settings).
 *
 * Menguji endpoint yang menyediakan data pengaturan situs (seperti nama situs, kontak, dll)
 * untuk digunakan oleh frontend aplikasi.
 *
 * ATURAN BISNIS UTAMA:
 * - Pengaturan disediakan dalam format key-value pair yang rata (flat) untuk kemudahan akses di frontend.
 * - Endpoint harus mendukung penyaringan pengaturan berdasarkan kategori grup (misal: 'general', 'contact').
 */

use App\Models\Setting;
use function Pest\Laravel\getJson;

/**
 * Skenario: Menampilkan Seluruh Pengaturan Sistem.
 * Prosedur: Membuat beberapa record pengaturan dari berbagai grup dan memanggil indeks.
 * Ekspektasi: Seluruh pengaturan dirender sebagai dictionary key-value secara langsung.
 */
test('index menampilkan semua setting sebagai key-value', function () {
    // Arrange: Menyiapkan beberapa pengaturan (Setting) yang merepresentasikan konfigurasi sistem.
    Setting::factory()->create(['key' => 'site_name', 'value' => 'Al Ghazaly', 'group' => 'general']);
    Setting::factory()->create(['key' => 'phone', 'value' => '08123456', 'group' => 'contact']);

    // Act: Memanggil endpoint GET untuk seluruh pengaturan sistem tanpa filter.
    $response = getJson('/api/settings');

    // Assert: Endpoint harus merender pengaturan dalam bentuk flat JSON agar mudah dibaca client.
    $response->assertStatus(200)
             ->assertJsonFragment(['site_name' => 'Al Ghazaly'])
             ->assertJsonFragment(['phone' => '08123456']);
});

/**
 * Skenario: Memfilter Pengaturan Berdasarkan Grup.
 * Prosedur: Meminta pengaturan hanya untuk grup spesifik (misal: 'general').
 * Ekspektasi: Sistem mengembalikan hanya pengaturan yang tergabung dalam grup yang diminta.
 */
test('index filter by group', function () {
    // Arrange: Membuat pengaturan dari dua grup berbeda: 'general' dan 'contact'.
    Setting::factory()->create(['key' => 'site_name', 'value' => 'Al Ghazaly', 'group' => 'general']);
    Setting::factory()->create(['key' => 'phone', 'value' => '08123456', 'group' => 'contact']);

    // Act: Memanggil endpoint GET dengan menggunakan parameter query ?group=general.
    $response = getJson('/api/settings?group=general');

    // Assert: Memastikan bahwa respons hanya memuat konfigurasi grup 'general',
    //         sedangkan pengaturan grup 'contact' tidak ada dalam hasil respons.
    $response->assertStatus(200)
             ->assertJsonFragment(['site_name' => 'Al Ghazaly']);
    // Should NOT contain contact group
    $response->assertJsonMissing(['phone' => '08123456']);
});

/**
 * Skenario: Penanganan Ketika Pengaturan Masih Kosong.
 * Prosedur: Memanggil endpoint ketika belum ada satupun record pengaturan di database.
 * Ekspektasi: Mengembalikan status 200 OK dengan respons objek/array JSON kosong.
 */
test('index kosong jika belum ada data', function () {
    // Arrange: Basis data dalam kondisi kosong (tidak ada data pada tabel settings).

    // Act: Melakukan pemanggilan ke endpoint pengaturan publik.
    $response = getJson('/api/settings');

    // Assert: Tetap mengembalikan HTTP 200 OK secara aman tanpa menyebabkan error sistem.
    $response->assertStatus(200);
});
