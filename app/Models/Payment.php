<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Merepresentasikan satu transaksi pembayaran biaya PPDB via gateway Midtrans.
 *
 * Siklus hidup status pembayaran (dikelola oleh webhook Midtrans):
 * - `pending`  : Token Snap telah dibuat, menunggu aksi pembayaran dari pendaftar.
 * - `paid`     : Pembayaran dikonfirmasi lunas (settlement/capture dari Midtrans).
 * - `failed`   : Pembayaran gagal (cancel/deny dari Midtrans atau bank).
 * - `expired`  : Batas waktu pembayaran terlampaui.
 *
 * Field `snap_token` adalah token sesi pembayaran Midtrans Snap yang digunakan
 * oleh frontend untuk menampilkan popup/halaman pembayaran. Token ini bersifat
 * sementara dan memiliki masa berlaku yang dikonfigurasi di dashboard Midtrans.
 *
 * @property int             $payment_id
 * @property int             $registration_id
 * @property int|null        $user_id         ID admin yang membuat tagihan, null jika otomatis.
 * @property string          $order_id        ID unik order untuk rekonsiliasi dengan Midtrans.
 * @property string|null     $transaction_id  ID transaksi dari Midtrans (diisi via webhook).
 * @property float           $amount
 * @property string          $currency        Default: 'IDR'.
 * @property string|null     $payment_type    Metode bayar (bank_transfer, gopay, dll.) dari Midtrans.
 * @property string          $status          Nilai: pending|paid|failed|expired.
 * @property \Carbon\Carbon|null $paid_at
 * @property \Carbon\Carbon|null $expired_at
 * @property string|null     $snap_token      Token sesi Midtrans Snap untuk frontend.
 * @property array|null      $metadata        Data tambahan dari response Midtrans (JSON).
 */
class Payment extends Model
{
    use HasFactory;

    /** @var string Primary key non-standar (bukan 'id'). */
    protected $primaryKey = 'payment_id';

    /** @var list<string> */
    protected $fillable = [
        'registration_id',
        'user_id',
        'order_id',
        'transaction_id',
        'amount',
        'currency',
        'payment_type',
        'status',
        'paid_at',
        'expired_at',
        'snap_token',
        'metadata',
    ];

    /**
     * @var array<string, string>
     * `metadata` di-cast ke array PHP untuk akses langsung seperti `$payment->metadata['bank']`.
     */
    protected $casts = [
        'amount'     => 'decimal:2',
        'paid_at'    => 'datetime',
        'expired_at' => 'datetime',
        'metadata'   => 'array',
    ];

    /**
     * Pendaftaran PPDB yang terhubung dengan pembayaran ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Registration, Payment>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id', 'registration_id');
    }

    /**
     * Admin yang menginisiasi pembuatan tagihan pembayaran ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Payment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
