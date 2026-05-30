<?php

/**
 * Suite Pengujian: Fitur Manajemen Pembayaran Midtrans (Admin Panel).
 *
 * Menguji kapabilitas admin dalam melihat riwayat transaksi keuangan PPDB.
 * Modul ini berfungsi sebagai antarmuka read-only karena mutasi status payment
 * dikendalikan penuh oleh webhook callback dari Midtrans.
 *
 * ATURAN BISNIS UTAMA:
 * - Admin hanya diberikan hak melihat dan memfilter data (Read-only module).
 * - Pemfilteran bisa dilakukan berdasarkan status ('paid', 'pending', dsb).
 * - Pencarian spesifik didukung via Order ID (Kunci unik yang dikirim ke Midtrans).
 */

use App\Models\Payment;
use App\Models\Registration;
use function Pest\Laravel\{getJson, actingAs};

/**
 * Setup Global: Inject hak akses admin pada siklus test.
 */
beforeEach(function () {
    $this->adminUser = createAdminUser();
});

/**
 * Skenario: Konfirmasi Restriksi Pengunjung Non-Admin.
 * Prosedur: Membaca entri rute tanpa kredensial valid.
 * Ekspektasi: API sekuritas akan meretur 401 Unauthenticated.
 */
test('akses tanpa auth ditolak', function () {
    // Act: Hit list payment tanpa login.
    $response = getJson('/api/admin/payments');

    // Assert: Sukses diblokir.
    $response->assertStatus(401);
});

/**
 * Skenario: Tampilan Antrean Seluruh Riwayat Pembayaran PPDB.
 * Prosedur: Admin melakukan GET request ke rute indeks pembayaran.
 * Ekspektasi: Respons paginasi dari tabel payments ditarik seluruhnya.
 */
test('index menampilkan semua payment (paginated)', function () {
    // Arrange: Tiga rekaman transaksi palsu.
    Payment::factory()->count(3)->create();

    // Act: Pengambilan dari endpoint dengan role admin.
    $response = actingAs($this->adminUser)->getJson('/api/admin/payments');

    // Assert: Sesuai harapan standar paginasi framework (Memuat data array dan metadata page).
    $response->assertStatus(200)
             ->assertJsonStructure(['current_page', 'data', 'per_page', 'total']);
});

/**
 * Skenario: Pemisahan Riwayat Transaksi Berdasarkan Status.
 * Prosedur: Memfilter laporan dengan parameter query string `?status=paid`.
 * Ekspektasi: API menyingkirkan pembayaran tertunda (pending) dari layar admin.
 */
test('index filter by status', function () {
    // Arrange: Buat 1 transaksi yang telah dibayar lunas, dan 1 yang masih menggantung.
    Payment::factory()->create(['status' => 'paid']);
    Payment::factory()->create(['status' => 'pending']);

    // Act: Tembakan request dengan filter khusus paid.
    $response = actingAs($this->adminUser)->getJson('/api/admin/payments?status=paid');

    // Assert: Pembuktian bahwa data kotor (pending) tak terbawa.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['status'])->toBe('paid');
});

/**
 * Skenario: Kemampuan Pencarian Cepat Berbasis Resi (Order ID).
 * Prosedur: Menyediakan fitur lacak untuk validasi silang (cross-check) resi Midtrans secara instan.
 * Ekspektasi: Hanya transaksi dengan string Order ID bersangkutan yang keluar.
 */
test('index filter by search order_id', function () {
    // Arrange: Set-up transaksi dengan Order ID yang kontras polanya.
    Payment::factory()->create(['order_id' => 'ORDER-SEARCH-TEST']);
    Payment::factory()->create(['order_id' => 'ORDER-OTHER-123']);

    // Act: Hit menggunakan sebagian keyword (SEARCH-TEST).
    $response = actingAs($this->adminUser)->getJson('/api/admin/payments?search=SEARCH-TEST');

    // Assert: Array hasil berisi 1 elemen yang presisi.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
});

/**
 * Skenario: Pembacaan Lembar Rincian (Invoice Detail).
 * Prosedur: Admin membuka kuitansi lengkap milik satu pendaftar.
 * Ekspektasi: Seluruh metadata pembayaran (Nominal, Tipe Pembayaran, Tgl, dst) ditarik akurat.
 */
test('show payment detail', function () {
    // Arrange: Persiapkan 1 profil bayar.
    $payment = Payment::factory()->create();

    // Act: Request Get tunggal via PK payment_id.
    $response = actingAs($this->adminUser)->getJson('/api/admin/payments/' . $payment->payment_id);

    // Assert: ID Primary Key bersesuaian murni.
    $response->assertStatus(200)
             ->assertJsonPath('payment_id', $payment->payment_id);
});

/**
 * Skenario: Penanganan Pencarian Invoice Bodong.
 * Prosedur: Pengecekan pada resi yang tidak pernah diproduksi sistem.
 * Ekspektasi: Respons Not Found (404) aman tanpa melempar stack trace error.
 */
test('show payment tidak ada (404)', function () {
    // Act: Cari indeks 9999 yang kosong.
    $response = actingAs($this->adminUser)->getJson('/api/admin/payments/9999');

    // Assert: Teredam dengan 404.
    $response->assertStatus(404);
});
