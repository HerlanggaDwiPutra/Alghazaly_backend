<?php

/**
 * Suite Pengujian: Endpoint Publik Data Alumni Sekolah.
 *
 * Menguji bahwa galeri alumni hanya menampilkan data yang sudah dipublikasikan,
 * mendukung filter tahun kelulusan dan pencarian nama, serta pengurutan yang benar.
 *
 * ATURAN BISNIS UTAMA:
 * - Alumni dengan `is_published = false` tidak boleh tampil di halaman publik.
 * - Pengurutan berdasarkan `graduation_year` descending menampilkan angkatan terbaru
 *   di posisi teratas halaman galeri alumni.
 */

use App\Models\Alumni;
use function Pest\Laravel\getJson;

/**
 * Skenario: Keamanan Data — Alumni Tidak Dipublikasikan Tidak Bocor ke Publik.
 * Prosedur: Membuat dua alumni (satu published, satu unpublished) dan memanggil index.
 * Ekspektasi: Hanya satu alumni yang dikembalikan — yang berstatus is_published = true.
 */
test('index hanya tampilkan alumni published', function () {
    // Arrange: Membuat dua alumni dengan status publikasi berbeda.
    Alumni::factory()->create(['is_published' => true]);
    Alumni::factory()->create(['is_published' => false]);

    // Act: GET request ke endpoint galeri alumni publik.
    $response = getJson('/api/alumni');

    // Assert: Hanya 1 alumni dalam response — yang is_published = false disaring.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
});

/**
 * Skenario: Filter Tahun — Alumni Ditampilkan Sesuai Tahun Kelulusan.
 * Prosedur: Membuat dua alumni dengan tahun kelulusan berbeda dan memfilter per tahun.
 * Ekspektasi: Hanya alumni dari tahun 2023 yang dikembalikan ketika filter `year=2023`.
 */
test('index filter by year', function () {
    // Arrange: Dua alumni published dengan tahun kelulusan berbeda.
    Alumni::factory()->create(['is_published' => true, 'graduation_year' => 2023]);
    Alumni::factory()->create(['is_published' => true, 'graduation_year' => 2024]);

    // Act: GET request dengan filter tahun kelulusan.
    $response = getJson('/api/alumni?year=2023');

    // Assert: Hanya 1 alumni yang dikembalikan dengan graduation_year yang sesuai filter.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['graduation_year'])->toBe(2023);
});

/**
 * Skenario: Filter Pencarian — Alumni Ditemukan Berdasarkan Nama.
 * Prosedur: Membuat dua alumni dengan nama berbeda dan mencari menggunakan kata kunci.
 * Ekspektasi: Hanya alumni yang namanya mengandung kata kunci pencarian yang dikembalikan.
 */
test('index filter by search name', function () {
    // Arrange: Dua alumni dengan nama yang kontras untuk memvalidasi filter LIKE.
    Alumni::factory()->create(['is_published' => true, 'name' => 'Ahmad Fauzi']);
    Alumni::factory()->create(['is_published' => true, 'name' => 'Budi Santoso']);

    // Act: GET request dengan filter pencarian nama.
    $response = getJson('/api/alumni?search=Ahmad');

    // Assert: Hanya Ahmad Fauzi yang dikembalikan; Budi Santoso tidak cocok.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['name'])->toBe('Ahmad Fauzi');
});

/**
 * Skenario: Pengurutan — Alumni Diurutkan dari Angkatan Terbaru ke Terlama.
 * Prosedur: Membuat tiga alumni dengan tahun kelulusan berbeda secara acak.
 * Ekspektasi: Alumni diurutkan descending berdasarkan graduation_year.
 *             Angkatan 2024 tampil pertama, 2022 kedua, 2020 terakhir.
 */
test('index urut by graduation_year desc', function () {
    // Arrange: Tiga alumni published dengan tahun kelulusan yang dimasukkan secara tidak berurutan.
    Alumni::factory()->create(['is_published' => true, 'graduation_year' => 2020]);
    Alumni::factory()->create(['is_published' => true, 'graduation_year' => 2024]);
    Alumni::factory()->create(['is_published' => true, 'graduation_year' => 2022]);

    // Act: GET request tanpa filter — semua alumni published.
    $response = getJson('/api/alumni');

    // Assert: Urutan graduation_year dalam response adalah 2024 → 2022 → 2020.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data[0]['graduation_year'])->toBe(2024);
    expect($data[1]['graduation_year'])->toBe(2022);
    expect($data[2]['graduation_year'])->toBe(2020);
});
