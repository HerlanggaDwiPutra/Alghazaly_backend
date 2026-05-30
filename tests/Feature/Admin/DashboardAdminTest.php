<?php

/**
 * Suite Pengujian: Tampilan Rangkuman Dashboard Analitik (Admin Panel).
 *
 * Menguji integrasi penggabungan berbagai tabel menjadi ringkasan matrik operasional.
 * Kompilasi mencakup status pembayaran pendaftaran, lalu lintas publikasi post, 
 * jumlah pengunjung web, serta tren pengguna terbaru.
 */

use App\Models\FormSubmission;
use App\Models\Post;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use function Pest\Laravel\{getJson, actingAs};

/**
 * Setup Global: Mengunci siklus testing dan mengkoneksikan kepada global test helper
 * dari admin instance (Multi-role bypass).
 */
beforeEach(function () {
    $this->adminUser = createAdminUser();
});

/**
 * Skenario: Memblokir Permintaan Tinjauan Data Agregasi Secara Eksternal.
 * Prosedur: Mengintip rute dashboard tanpa header pengunci valid (401).
 * Ekspektasi: Sistem memproteksi ringkasan internal sekolah dari telinga luar.
 */
test('akses tanpa auth ditolak', function () {
    // Arrange: Sesi dibiarkan kosong.
    // Act: Paksaan penetrasi endpoint.
    $response = getJson('/api/admin/dashboard');

    // Assert: Sesuai pedoman sekuritas Laravel Sanctum.
    $response->assertStatus(401);
});

/**
 * Skenario: Memverifikasi Keutuhan Struktur JSON Multi-Tabel.
 * Prosedur: Mengakses dan membeberkan form dasar payload matrix.
 * Ekspektasi: Tidak ada cabang node JSON yang membolos pada hasil query agregrasi,
 *             memenuhi kebutuhan charting (grafik) pada UI/UX frontend.
 */
test('index menampilkan statistik dashboard', function () {
    // Arrange: Tanpa populasi data khusus.

    // Act: Hit modul.
    $response = actingAs($this->adminUser)->getJson('/api/admin/dashboard');

    // Assert: Memastikan bahwa kerangka dasar objek (Registrations, Payments, Posts dll) beroperasi sesuai arsitektur.
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'registrations' => ['total', 'pending', 'accepted', 'rejected'],
                 'payments'      => ['total', 'paid', 'pending'],
                 'posts',
                 'users',
                 'unread_messages',
                 'recent_registrations',
             ]);
});

/**
 * Skenario: Hitung Matematis Matrik Progres Registrasi PPDB.
 * Prosedur: Melakukan agregasi otomatis dengan merangkum total pendaftar berdasar status pendaftaran.
 * Ekspektasi: Matriks perhitungan penjumlahan sama dengan apa yang di input.
 */
test('index menghitung registrations by status', function () {
    // Arrange: Kombinasi kalkulasi 2 pending, 1 accepted, dan 1 rejected.
    Registration::factory()->count(2)->create(['status' => 'pending']);
    Registration::factory()->create(['status' => 'accepted']);
    Registration::factory()->create(['status' => 'rejected']);

    // Act: Endpoint dipanggil untuk merefresh agregasi di controller.
    $response = actingAs($this->adminUser)->getJson('/api/admin/dashboard');

    // Assert: Penjumlahan dari setiap properti harus cocok nilainya satu sama lain dengan tepat.
    $response->assertStatus(200);
    $data = $response->json('registrations');
    expect($data['total'])->toBe(4);
    expect($data['pending'])->toBe(2);
    expect($data['accepted'])->toBe(1);
    expect($data['rejected'])->toBe(1);
});

/**
 * Skenario: Kalkulasi Akuntansi Penerimaan Transaksi Payment Midtrans.
 * Prosedur: Menyedot perhitungan arus kas berdasarkan status penyelesaian invoice payment.
 * Ekspektasi: Memastikan rekapitulasi dana berstatus 'paid' maupun yang masih mengendap terkonfigurasi baik.
 */
test('index menghitung payments by status', function () {
    // Arrange: Dummy untuk simulasi uang yang masuk 1 dan uang yang nyangkut 2.
    Payment::factory()->count(2)->create(['status' => 'pending']);
    Payment::factory()->create(['status' => 'paid']);

    // Act: Hit ke controller.
    $response = actingAs($this->adminUser)->getJson('/api/admin/dashboard');

    // Assert: Kalkulasi jumlah elemen akuntansi berhasil diprediksi nilainya.
    $response->assertStatus(200);
    $data = $response->json('payments');
    expect($data['total'])->toBe(3);
    expect($data['paid'])->toBe(1);
    expect($data['pending'])->toBe(2);
});

/**
 * Skenario: Mengontrol Beban Muatan Log Calon Siswa (Terbaru).
 * Prosedur: Mengatasi tumpukan notifikasi registrasi dengan melimit batas maksimal penampilan (maks 5 list elemen widget).
 * Ekspektasi: Tampilan UI tidak meledak melampaui slot yang disediakan karena list tidak pernah dibatasi pada query.
 */
test('index menampilkan recent registrations (max 5)', function () {
    // Arrange: Membanjiri data secara artifisial dengan menyuntikkan 7 list pendaftar berturut-turut.
    Registration::factory()->count(7)->create();

    // Act: Endpoint merespons beban muatan tersebut.
    $response = actingAs($this->adminUser)->getJson('/api/admin/dashboard');

    // Assert: Fitur pembatasan muat mencegah kebocoran dengan mempertahankan beban list maksimal ≤ 5 pendaftar di menu terbaru.
    $response->assertStatus(200);
    $recent = $response->json('recent_registrations');
    expect(count($recent))->toBeLessThanOrEqual(5);
});
