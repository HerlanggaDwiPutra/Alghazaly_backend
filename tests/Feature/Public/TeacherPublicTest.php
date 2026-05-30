<?php

/**
 * Suite Pengujian: Endpoint Publik Profil Guru (Teachers).
 *
 * Menguji bahwa halaman profil tenaga pendidik sekolah dikembalikan dengan benar.
 *
 * ATURAN BISNIS UTAMA:
 * - Hanya profil guru yang berstatus aktif (`is_active = true`) yang boleh ditampilkan.
 * - Profil guru harus diurutkan secara spesifik sesuai dengan kolom `order` untuk
 *   menentukan struktur hierarki (misal: Kepala Sekolah pertama, baru wali kelas).
 */

use App\Models\Teacher;
use function Pest\Laravel\getJson;

/**
 * Skenario: Profil Guru Non-Aktif Tidak Muncul di Publik.
 * Prosedur: Menyiapkan profil aktif dan non-aktif, kemudian mengakses indeks.
 * Ekspektasi: Hanya guru yang masih aktif yang ditampilkan pada respons API.
 */
test('index hanya tampilkan guru aktif', function () {
    // Arrange: Mendaftarkan dua profil guru, satu berstatus aktif dan satu non-aktif (pensiun/pindah).
    Teacher::factory()->create(['is_active' => true, 'order' => 1]);
    Teacher::factory()->create(['is_active' => false, 'order' => 2]);

    // Act: Mengakses endpoint profil guru untuk publik.
    $response = getJson('/api/teachers');

    // Assert: Endpoint hanya mengembalikan 1 data (guru yang masih aktif).
    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(1);
});

/**
 * Skenario: Indeks Kosong Jika Semua Guru Non-Aktif.
 * Prosedur: Hanya ada profil guru dengan `is_active = false`.
 * Ekspektasi: Mengembalikan daftar kosong karena tidak ada profil yang layak terpublikasi.
 */
test('index tidak tampilkan guru non-aktif', function () {
    // Arrange: Membuat profil guru namun dalam keadaan dinonaktifkan.
    Teacher::factory()->create(['is_active' => false]);

    // Act: Melakukan permintaan indeks profil guru.
    $response = getJson('/api/teachers');

    // Assert: Hasilnya adalah list kosong (0), mencegah profil mantan pengajar muncul.
    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(0);
});

/**
 * Skenario: Pengurutan Profil Guru Berdasarkan Kolom Prioritas (Order).
 * Prosedur: Memasukkan profil guru secara tidak teratur, namun memiliki nilai atribut 'order'.
 * Ekspektasi: Respons harus disusun sesuai nilai atribut `order` (Ascending).
 */
test('index urut by order', function () {
    // Arrange: Membuat 3 profil guru dengan urutan ('order') yang diacak saat entri data.
    Teacher::factory()->create(['is_active' => true, 'order' => 3, 'name' => 'Guru C']);
    Teacher::factory()->create(['is_active' => true, 'order' => 1, 'name' => 'Guru A']);
    Teacher::factory()->create(['is_active' => true, 'order' => 2, 'name' => 'Guru B']);

    // Act: Mengakses daftar urutan guru.
    $response = getJson('/api/teachers');

    // Assert: Memastikan bahwa hasil yang dikembalikan diurutkan dengan patokan `order` ASC (A, B, C).
    $response->assertStatus(200);
    $data = $response->json();
    expect($data[0]['name'])->toBe('Guru A');
    expect($data[1]['name'])->toBe('Guru B');
    expect($data[2]['name'])->toBe('Guru C');
});
