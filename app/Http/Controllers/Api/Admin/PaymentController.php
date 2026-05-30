<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mengelola tampilan dan penelusuran data pembayaran di panel Admin.
 *
 * Controller ini bersifat read-only; tidak ada operasi create, update, atau
 * delete pada transaksi pembayaran karena mutasi data pembayaran dilakukan
 * secara eksklusif melalui callback webhook Midtrans ({@see WebhookController}).
 *
 * Seluruh endpoint di controller ini dilindungi middleware `auth:sanctum`
 * yang didefinisikan pada grup rute admin di routes/api.php.
 */
class PaymentController extends Controller
{
    /**
     * Menampilkan daftar seluruh transaksi pembayaran dengan paginasi.
     *
     * Mendukung dua parameter filter opsional melalui query string:
     * - `status`: Menyaring berdasarkan status pembayaran (pending, paid, failed, expired).
     * - `search`: Pencarian parsial pada kolom `order_id` menggunakan operator LIKE.
     *
     * Eager loading `registration` dibatasi hanya pada kolom identitas pendaftar
     * untuk mengurangi ukuran payload response dan overhead query.
     *
     * @param  \Illuminate\Http\Request  $request  Query string filter (status, search).
     * @return \Illuminate\Http\JsonResponse         Data pembayaran terpaginasi (15 per halaman).
     */
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::with('registration:registration_id,full_name,registration_number')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where('order_id', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($payments);
    }

    /**
     * Menampilkan detail satu transaksi pembayaran beserta relasi lengkapnya.
     *
     * Memuat seluruh data registrasi terkait dan informasi pengguna admin
     * yang menginisiasi pembayaran (jika pembayaran dibuat dari panel admin).
     *
     * @param  int  $id  Primary key dari tabel `payments` (payment_id).
     * @return \Illuminate\Http\JsonResponse  Detail pembayaran, atau 404 jika tidak ditemukan.
     */
    public function show(int $id): JsonResponse
    {
        return response()->json(Payment::with('registration', 'user:id,name,email')->findOrFail($id));
    }
}
