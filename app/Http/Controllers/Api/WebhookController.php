<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Menangani seluruh notifikasi callback (webhook) dari gateway pembayaran
 * pihak ketiga yang diintegrasikan dengan sistem Al Ghazaly.
 *
 * Controller ini bersifat stateless dan tidak memerlukan autentikasi Sanctum,
 * melainkan menggunakan mekanisme verifikasi signature key dari Midtrans
 * sebagai pengganti autentikasi standar.
 *
 * @see https://docs.midtrans.com/reference/receiving-response-and-notification
 */
class WebhookController extends Controller
{
    /**
     * Menangani callback notifikasi status transaksi dari API Midtrans.
     *
     * Alur kerja method ini:
     * 1. Memverifikasi keaslian request menggunakan SHA-512 signature key.
     * 2. Menemukan data pembayaran berdasarkan order_id dari payload.
     * 3. Memetakan status transaksi Midtrans ke status internal aplikasi.
     * 4. Memperbarui record pembayaran dan secara otomatis mengubah status
     *    registrasi pendaftar menjadi 'verified' jika pembayaran lunas.
     *
     * Endpoint ini wajib terdaftar tanpa middleware `auth:sanctum` di file
     * routes/api.php agar server Midtrans dapat mengaksesnya secara publik.
     *
     * @param  \Illuminate\Http\Request  $request  Payload JSON dari server Midtrans.
     * @return \Illuminate\Http\JsonResponse        Response 200 OK untuk Midtrans, atau 403/404 jika gagal.
     */
    public function midtrans(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Rekonstruksi signature key di sisi server untuk verifikasi.
        // Formula: SHA-512(order_id + status_code + gross_amount + server_key)
        // sesuai spesifikasi resmi Midtrans Notification Verification.
        $serverKey    = config('services.midtrans.server_key');
        $orderId      = $payload['order_id'] ?? '';
        $statusCode   = $payload['status_code'] ?? '';
        $grossAmount  = $payload['gross_amount'] ?? '';
        $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        // Tolak request jika signature tidak cocok untuk mencegah pemalsuan
        // notifikasi dari pihak yang tidak berwenang (Replay/Forgery Attack).
        if ($signatureKey !== ($payload['signature_key'] ?? '')) {
            Log::warning('Midtrans webhook: signature tidak valid', ['order_id' => $orderId]);
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $payment = Payment::where('order_id', $orderId)->first();

        if (! $payment) {
            return response()->json(['message' => 'Order tidak ditemukan.'], 404);
        }

        $transactionStatus = $payload['transaction_status'];

        // Pemetaan status transaksi Midtrans → status internal aplikasi.
        // 'capture' berlaku untuk pembayaran kartu kredit yang berhasil di-capture.
        // 'settlement' berlaku untuk transfer bank / virtual account yang telah diselesaikan.
        // Status yang tidak dikenali tidak diubah (dipertahankan nilai sebelumnya).
        $paymentStatus = match ($transactionStatus) {
            'capture', 'settlement' => 'paid',
            'cancel', 'deny'        => 'failed',
            'expire'                => 'expired',
            default                 => $payment->status,
        };

        $payment->update([
            'transaction_id' => $payload['transaction_id'] ?? $payment->transaction_id,
            'payment_type'   => $payload['payment_type'] ?? $payment->payment_type,
            'status'         => $paymentStatus,
            // Catat waktu pembayaran lunas secara tepat; jangan timpa jika belum 'paid'.
            'paid_at'        => $paymentStatus === 'paid' ? now() : $payment->paid_at,
        ]);

        // Bisnis Rule PPDB: Status registrasi hanya diubah ke 'verified' secara otomatis
        // jika pembayaran lunas DAN registrasi masih dalam status awal 'pending'.
        // Kondisi WHERE status='pending' mencegah downgrade status yang sudah diproses admin.
        if ($paymentStatus === 'paid' && $payment->registration_id) {
            Registration::where('registration_id', $payment->registration_id)
                ->where('status', 'pending')
                ->update(['status' => 'verified']);
        }

        return response()->json(['message' => 'OK']);
    }
}
