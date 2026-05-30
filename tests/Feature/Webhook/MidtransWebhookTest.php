<?php

/**
 * Suite Pengujian: Integrasi Webhook Midtrans — Titik Temu Sistem dengan Gateway Pembayaran.
 *
 * Berkas ini menguji seluruh skenario callback yang dikirimkan oleh Midtrans ke endpoint
 * `/api/webhooks/midtrans` setelah pembayaran biaya PPDB diproses oleh gateway.
 *
 * ARSITEKTUR KEAMANAN YANG DIUJI:
 * Midtrans menggunakan mekanisme Signature Key berbasis SHA-512 untuk memastikan bahwa
 * request callback berasal dari server Midtrans yang sah, bukan dari pihak ketiga yang
 * mencoba memanipulasi status pembayaran. Formula kalkulasi:
 *   SHA-512(order_id + status_code + gross_amount + server_key)
 *
 * Setiap tes dalam berkas ini memanipulasi `Config::set` untuk mengisolasi server key
 * dari konfigurasi environment produksi, memastikan tes tidak bergantung pada `.env` aktual.
 *
 * PEMETAAN STATUS TRANSAKSI MIDTRANS → SISTEM INTERNAL:
 * | Midtrans `transaction_status` | Status `payments.status` | Dampak pada `registrations.status` |
 * |-------------------------------|--------------------------|-------------------------------------|
 * | settlement                    | paid                     | pending → verified                  |
 * | cancel                        | failed                   | (tidak berubah)                     |
 * | expire                        | expired                  | (tidak berubah)                     |
 * | pending                       | pending (tidak berubah)  | (tidak berubah)                     |
 */

use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Support\Facades\Config;
use function Pest\Laravel\postJson;

/**
 * Skenario: Pembayaran Lunas — Alur Happy Path (Settlement).
 * Prosedur: Mensimulasikan callback Midtrans dengan status `settlement` dan
 *           signature key yang dihitung secara benar menggunakan formula SHA-512.
 * Ekspektasi: Status payment berubah menjadi `paid` sebagai bukti bahwa sistem
 *             memproses konfirmasi pembayaran lunas dari gateway dengan benar.
 */
test('webhook signature valid settlement', function () {
    // Arrange: Menyiapkan data pendaftaran dan payment dalam status awal 'pending'.
    //          Server key diisolasi via Config::set agar tes tidak bergantung pada .env produksi.
    Config::set('services.midtrans.server_key', 'test-server-key');

    $registration = Registration::factory()->create(['status' => 'pending']);
    $payment = Payment::factory()->create([
        'registration_id' => $registration->registration_id,
        'order_id' => 'ORDER-123',
        'status' => 'pending',
    ]);

    $orderId = 'ORDER-123';
    $statusCode = '200';
    $grossAmount = '500000.00';
    // Menghitung signature key yang valid menggunakan formula resmi Midtrans (SHA-512).
    // Formula: hash('sha512', order_id + status_code + gross_amount + server_key)
    $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . 'test-server-key');

    // Act: Mengirimkan POST request ke endpoint webhook publik yang
    //      meniru callback resmi dari server Midtrans setelah pembayaran dikonfirmasi.
    $response = postJson('/api/webhooks/midtrans', [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => 'settlement',
        'signature_key' => $signatureKey,
    ]);

    // Assert: Memverifikasi respons HTTP 200 dan perubahan status payment di database.
    //         Status 'paid' membuktikan bahwa pipeline pemrosesan settlement berjalan utuh.
    $response->assertStatus(200)
             ->assertJson(['message' => 'OK']);

    $this->assertDatabaseHas('payments', [
        'payment_id' => $payment->payment_id,
        'status' => 'paid',
    ]);
});

/**
 * Skenario: Gerbang Keamanan — Penolakan Signature Key Palsu (Sad Path).
 * Prosedur: Mengirimkan request callback dengan signature key yang tidak cocok dengan
 *           hasil kalkulasi SHA-512 yang seharusnya.
 * Ekspektasi: Sistem harus menolak request dengan HTTP 403, melindungi data pembayaran
 *             dari manipulasi oleh pihak tidak berwenang.
 */
test('webhook signature invalid', function () {
    // Arrange: Menyiapkan server key yang valid di konfigurasi, namun payload yang akan
    //          dikirim menggunakan signature key yang salah/dipalsukan.
    Config::set('services.midtrans.server_key', 'test-server-key');

    // Act: Mengirim request dengan 'invalid-signature' yang tidak cocok dengan hasil
    //      SHA-512 dari kombinasi field yang ada — mensimulasikan serangan spoofing.
    $response = postJson('/api/webhooks/midtrans', [
        'order_id' => 'ORDER-123',
        'status_code' => '200',
        'gross_amount' => '500000.00',
        'transaction_status' => 'settlement',
        'signature_key' => 'invalid-signature',
    ]);

    // Assert: Sistem harus mengembalikan HTTP 403 (Forbidden), bukan 200 atau 404.
    //         Ini membuktikan bahwa gerbang validasi signature aktif dan berfungsi.
    $response->assertStatus(403)
             ->assertJson(['message' => 'Invalid signature.']);
});

/**
 * Skenario: Transaksi Dibatalkan — Transisi Status ke `failed`.
 * Prosedur: Mensimulasikan callback Midtrans dengan `transaction_status = cancel`,
 *           yang terjadi ketika pengguna membatalkan pembayaran sebelum selesai.
 * Ekspektasi: Status payment diubah menjadi `failed` untuk mencerminkan pembatalan.
 *             Status registrasi tidak ikut berubah (hanya payment yang diperbarui).
 */
test('webhook cancel mengubah status failed', function () {
    // Arrange: Menyiapkan payment dalam status 'pending' yang siap menerima notifikasi
    //          pembatalan dari Midtrans.
    Config::set('services.midtrans.server_key', 'test-server-key');

    $payment = Payment::factory()->create([
        'order_id' => 'ORDER-CANCEL',
        'status' => 'pending',
    ]);

    $orderId = 'ORDER-CANCEL';
    $statusCode = '200';
    $grossAmount = '500000.00';
    // Signature key yang sah untuk memastikan request melewati gerbang keamanan.
    $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . 'test-server-key');

    // Act: Mengirimkan callback dengan `transaction_status = cancel`
    //      yang meniru peristiwa pembatalan oleh pengguna di halaman pembayaran Midtrans.
    $response = postJson('/api/webhooks/midtrans', [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => 'cancel',
        'signature_key' => $signatureKey,
    ]);

    // Assert: HTTP 200 diterima dan status payment di database berubah menjadi 'failed'.
    $response->assertStatus(200);

    $this->assertDatabaseHas('payments', [
        'payment_id' => $payment->payment_id,
        'status' => 'failed',
    ]);
});

/**
 * Skenario: Transaksi Kedaluwarsa — Transisi Status ke `expired`.
 * Prosedur: Mensimulasikan callback Midtrans dengan `transaction_status = expire`,
 *           yang terjadi ketika batas waktu pembayaran habis tanpa ada aksi dari pengguna.
 * Ekspektasi: Status payment berubah menjadi `expired`. Aturan bisnis ini penting agar
 *             admin dapat mengidentifikasi pendaftar yang tidak menyelesaikan pembayaran.
 */
test('webhook expire mengubah status expired', function () {
    // Arrange: Menyiapkan payment 'pending' yang sudah melewati batas waktu pembayaran.
    Config::set('services.midtrans.server_key', 'test-server-key');

    $payment = Payment::factory()->create([
        'order_id' => 'ORDER-EXPIRE',
        'status' => 'pending',
    ]);

    $orderId = 'ORDER-EXPIRE';
    $statusCode = '200';
    $grossAmount = '500000.00';
    $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . 'test-server-key');

    // Act: Mengirimkan callback dengan `transaction_status = expire`
    //      yang meniru notifikasi dari Midtrans ketika waktu bayar habis.
    $response = postJson('/api/webhooks/midtrans', [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => 'expire',
        'signature_key' => $signatureKey,
    ]);

    // Assert: Status payment berubah dari 'pending' menjadi 'expired' di database.
    $response->assertStatus(200);

    $this->assertDatabaseHas('payments', [
        'payment_id' => $payment->payment_id,
        'status' => 'expired',
    ]);
});

/**
 * Skenario: Efek Berantai — Settlement Memicu Pembaruan Status Registrasi.
 * Prosedur: Mensimulasikan skenario di mana payment lunas (settlement) seharusnya
 *           memicu perubahan status registrasi PPDB dari 'pending' menjadi 'verified'.
 * Ekspektasi: Konfirmasi pembayaran lunas secara otomatis mengubah status pendaftaran
 *             siswa dari menunggu menjadi terverifikasi, siap diproses admin.
 */
test('webhook payment lunas update registrasi', function () {
    // Arrange: Menyiapkan registrasi dan payment yang terhubung (relasi FK registration_id).
    //          Keduanya dalam status 'pending' sebelum webhook diterima.
    Config::set('services.midtrans.server_key', 'test-server-key');

    $registration = Registration::factory()->create(['status' => 'pending']);
    $payment = Payment::factory()->create([
        'registration_id' => $registration->registration_id,
        'order_id' => 'ORDER-UPDATE',
        'status' => 'pending',
    ]);

    $orderId = 'ORDER-UPDATE';
    $statusCode = '200';
    $grossAmount = '500000.00';
    $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . 'test-server-key');

    // Act: Mengirim callback settlement yang valid untuk memicu pipeline:
    //      payment.status → 'paid' → registration.status → 'verified'.
    $response = postJson('/api/webhooks/midtrans', [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => 'settlement',
        'signature_key' => $signatureKey,
    ]);

    // Assert: Memverifikasi bahwa efek berantai (cascade update) bekerja dengan benar.
    //         Status registrasi berubah menjadi 'verified', bukan hanya payment-nya saja.
    $response->assertStatus(200);

    $this->assertDatabaseHas('registrations', [
        'registration_id' => $registration->registration_id,
        'status' => 'verified',
    ]);
});

/**
 * Skenario: Order ID Tidak Ditemukan — Penanganan Data Orphan dari Gateway.
 * Prosedur: Mengirimkan callback dengan order_id yang tidak ada di database payment.
 * Ekspektasi: Sistem mengembalikan HTTP 404 dengan pesan yang jelas. Ini penting agar
 *             callback dari transaksi yang tidak terdaftar tidak menyebabkan error 500.
 */
test('webhook order id tidak ditemukan', function () {
    // Arrange: Tidak ada payment yang dibuat — order ID yang dikirim tidak akan ditemukan.
    //          Signature key tetap valid agar tes melewati gerbang keamanan terlebih dahulu.
    Config::set('services.midtrans.server_key', 'test-server-key');

    $orderId = 'ORDER-NOTFOUND';
    $statusCode = '200';
    $grossAmount = '500000.00';
    $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . 'test-server-key');

    // Act: Mengirim request dengan order_id valid secara format tetapi tidak ada di database.
    $response = postJson('/api/webhooks/midtrans', [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => 'settlement',
        'signature_key' => $signatureKey,
    ]);

    // Assert: HTTP 404 dengan pesan yang informatif — sistem tidak crash dengan error 500.
    $response->assertStatus(404)
             ->assertJson(['message' => 'Order tidak ditemukan.']);
});

/**
 * Skenario: Notifikasi Pending — Status Payment Tidak Berubah (Idempotency).
 * Prosedur: Midtrans mengirimkan notifikasi `pending` ketika pembayaran masih diproses
 *           (contoh: transfer bank yang menunggu konfirmasi). Sistem harus mengakui
 *           notifikasi ini tanpa mengubah status payment yang sudah `pending`.
 * Ekspektasi: Payment tetap dalam status 'pending' — sistem bersifat idempoten untuk
 *             notifikasi yang tidak menghasilkan perubahan state.
 */
test('webhook status pending tidak mengubah status payment', function () {
    // Arrange: Payment sudah dalam status 'pending' (status awal). Notifikasi 'pending'
    //          dari Midtrans hanya sebagai konfirmasi bahwa pembayaran masih diproses.
    Config::set('services.midtrans.server_key', 'test-server-key');

    $payment = Payment::factory()->create([
        'order_id' => 'ORDER-PENDING',
        'status' => 'pending',
    ]);

    $orderId = 'ORDER-PENDING';
    $statusCode = '200';
    $grossAmount = '500000.00';
    $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . 'test-server-key');

    // Act: Mengirimkan callback dengan `transaction_status = pending`
    //      yang meniru notifikasi menunggu konfirmasi dari bank/e-wallet.
    $response = postJson('/api/webhooks/midtrans', [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => 'pending',
        'signature_key' => $signatureKey,
    ]);

    // Assert: HTTP 200 diterima (notifikasi diakui), namun status payment tidak berubah.
    //         Perilaku idempoten ini mencegah regresi status yang sudah lebih maju.
    $response->assertStatus(200);

    $this->assertDatabaseHas('payments', [
        'payment_id' => $payment->payment_id,
        'status' => 'pending',
    ]);
});
