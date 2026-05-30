<?php

/**
 * Suite Pengujian: Fitur Manajemen Pengaturan Situs (Admin Panel).
 *
 * Menguji panel konfigurasi yang digunakan admin untuk mengubah metadata dasar
 * dari identitas web app seperti nama situs, nomor telepon, atau alamat tanpa
 * melibatkan proses modifikasi kode front-end.
 *
 * ATURAN BISNIS UTAMA:
 * - Struktur setting dikelompokkan (grouped) secara fungsional (mis: general, contact).
 * - Fitur pembaruan diimplementasikan sebagai skema 'Batch Update' yang menerima
 *   banyak record update sekaligus dalam satu kali payload (Array of Key-Value).
 * - Operasi batch update (PUT) bertindak melakukan 'Upsert', di mana API akan
 *   menciptakan baris DB jika record tak ada, atau sekadar menimpa nilai DB jika telah ada (tanpa dobel id).
 */

use App\Models\Setting;
use function Pest\Laravel\{getJson, putJson, actingAs};

/**
 * Setup Global: Pemasangan otorisasi tingkat admin.
 */
beforeEach(function () {
    $this->adminUser = createAdminUser();
});

/**
 * Skenario: Pertahanan Layer API Pengaturan.
 * Prosedur: Mencoba menyedot konfigurasi dari gerbang panel tanpa bekal login token.
 * Ekspektasi: Mencegah serangan data scraping atau injeksi konfigurasi gelap dengan 401.
 */
test('akses tanpa auth ditolak', function () {
    // Act: Request get.
    $response = getJson('/api/admin/settings');

    // Assert: Cekal sukses.
    $response->assertStatus(401);
});

/**
 * Skenario: Penampilan Format Kamus Pengaturan Berbasis Kelompok (Grouped).
 * Prosedur: Mengambil indeks dari list properti Setting yang ada.
 * Ekspektasi: Data dari server laravel tak hanya datar, melainkan telah melalui proses format (Resource)
 *             berbasis key `group` sebelum disajikan utuh (Memudahkan parse form di UI frontend).
 */
test('index menampilkan settings grouped', function () {
    // Arrange: Ciptakan 2 konfigurasi beda grup (general vs contact).
    Setting::factory()->create(['key' => 'site_name', 'group' => 'general']);
    Setting::factory()->create(['key' => 'phone', 'group' => 'contact']);

    // Act: Hit get list pengaturan.
    $response = actingAs($this->adminUser)->getJson('/api/admin/settings');

    // Assert: Balasan json harus memiliki branch/node root bernama 'general' & 'contact'.
    $response->assertStatus(200);
    $data = $response->json();
    expect($data)->toHaveKey('general');
    expect($data)->toHaveKey('contact');
});

/**
 * Skenario: Prosedur Penggantian Nilai Massal (Batch Update / Upsert).
 * Prosedur: Menembak method HTTP PUT dengan muatan array of objek untuk disave serentak.
 * Ekspektasi: Menyimpan lebih dari 1 key pengaturan dalam sekalian hit API.
 */
test('update settings berhasil (batch)', function () {
    // Arrange: Tanpa Inisiasi DB (Mensimulasikan data kosong yang akan ter-insert via updateOrInsert).

    // Act: Menembakkan batch array 'settings' dengan 2 child di dalamnya.
    $response = actingAs($this->adminUser)->putJson('/api/admin/settings', [
        'settings' => [
            ['key' => 'site_name', 'value' => 'SMA Al Ghazaly'],
            ['key' => 'phone', 'value' => '08123456789'],
        ],
    ]);

    // Assert: Respon berhasil disimpan dan DB mematri 2 record tersebut pada MySQL.
    $response->assertStatus(200)
             ->assertJson(['message' => 'Pengaturan disimpan.']);

    $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'SMA Al Ghazaly']);
    $this->assertDatabaseHas('settings', ['key' => 'phone', 'value' => '08123456789']);
});

/**
 * Skenario: Proteksi Pencegahan Pengiriman Struktur Batch Salah (Array Kosong).
 * Prosedur: Admin (Front-end app) malah menyetor list konfigurasi yang string array-nya null.
 * Ekspektasi: Validator turun tangan dan memaksa parameter `settings` bersifat wajib ada.
 */
test('update settings validasi required array', function () {
    // Act: Pengiriman parameter settings sebagai array hampa.
    $response = actingAs($this->adminUser)->putJson('/api/admin/settings', []);

    // Assert: Error validator Unprocessable Entity (422) bertindak.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['settings']);
});

/**
 * Skenario: Validasi Anatomi Detail Dari Anak Elemen Array Batch (Dot Notation Array Test).
 * Prosedur: Ada objek array setting yang berisi value saja, namun melupakan properti id pengenal (`key`).
 * Ekspektasi: API mencegah penyimpanan data cacat berkat pendeteksian validasi berlapis `settings.*.key`.
 */
test('update settings validasi key required', function () {
    // Act: Mengumpankan array batch pertama (index 0) tapi tidak menyertakan key pengenal.
    $response = actingAs($this->adminUser)->putJson('/api/admin/settings', [
        'settings' => [
            ['value' => 'test'],
        ],
    ]);

    // Assert: Melempar notifikasi bahwa pada antrian elemen pertama (settings.0), parameter key tak diketemukan.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['settings.0.key']);
});

/**
 * Skenario: Skema Perlindungan Dobel Entri Key (Menghindari Duplicate Keys di DB).
 * Prosedur: Mem-patching konfigurasi milik 'site_name' yang nyatanya sebelumnya sudah terdaftar.
 * Ekspektasi: Nilai usangnya digusur nilai baru, namun JUMLAH hitungan data tidak boleh mendobel jadi 2.
 */
test('update settings update existing key', function () {
    // Arrange: Memberikan pasokan lawas milik setting "site_name".
    Setting::factory()->create(['key' => 'site_name', 'value' => 'Lama']);

    // Act: Admin me-request timpaan data.
    actingAs($this->adminUser)->putJson('/api/admin/settings', [
        'settings' => [
            ['key' => 'site_name', 'value' => 'Baru'],
        ],
    ]);

    // Assert: Data sukses tergantikan ("Baru" terbaca di basis data), dan di saat yang sama,
    // record total tetap 1 (Sistem Upsert berjalan mulus tanpa menimbulkan data sampah ganda).
    $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'Baru']);
    // Should not create duplicate
    expect(Setting::where('key', 'site_name')->count())->toBe(1);
});
