<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Merepresentasikan satu berkas pendaftaran PPDB (Penerimaan Peserta Didik Baru).
 *
 * Siklus hidup status pendaftaran:
 * - `pending`  : Status awal saat formulir pertama kali disubmit publik.
 * - `verified` : Ditetapkan otomatis oleh webhook Midtrans jika pembayaran lunas.
 * - `accepted` : Ditetapkan manual oleh admin setelah verifikasi berkas selesai.
 * - `rejected` : Ditetapkan manual oleh admin jika pendaftar tidak memenuhi syarat.
 *
 * Nomor pendaftaran (`registration_number`) bersifat publik dan digunakan
 * sebagai identifier self-service untuk pengecekan status tanpa login.
 *
 * @property int         $registration_id
 * @property string      $registration_number  Format: PPDB-{TAHUN}-{6 karakter acak}.
 * @property string      $full_name
 * @property \Carbon\Carbon $birth_date
 * @property string      $birth_place
 * @property string      $gender              Nilai: 'L' (Laki-laki) atau 'P' (Perempuan).
 * @property string      $address
 * @property string      $phone
 * @property string      $parent_name
 * @property string      $parent_phone
 * @property string      $previous_school
 * @property string      $academic_year
 * @property string      $status             Nilai: pending|verified|accepted|rejected.
 * @property string|null $notes              Catatan tinjauan dari admin.
 */
class Registration extends Model
{
    use HasFactory;

    /** @var string Primary key non-standar (bukan 'id'). */
    protected $primaryKey = 'registration_id';

    /** @var list<string> */
    protected $fillable = [
        'registration_number',
        'full_name',
        'birth_date',
        'birth_place',
        'gender',
        'address',
        'phone',
        'parent_name',
        'parent_phone',
        'previous_school',
        'academic_year',
        'status',
        'notes',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * Daftar berkas persyaratan yang diunggah untuk pendaftaran ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<RegistrationDocument>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class, 'registration_id', 'registration_id');
    }

    /**
     * Transaksi pembayaran yang terhubung dengan pendaftaran ini (satu-ke-satu).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<Payment>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'registration_id', 'registration_id');
    }
}
