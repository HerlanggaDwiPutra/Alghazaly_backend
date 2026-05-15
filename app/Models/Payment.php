<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;
    protected $primaryKey = 'payment_id';

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

    protected $casts = [
        'amount'   => 'decimal:2',
        'paid_at'  => 'datetime',
        'expired_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id', 'registration_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
