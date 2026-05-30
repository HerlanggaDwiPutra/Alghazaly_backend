<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Merepresentasikan akun pengguna panel Admin aplikasi Al Ghazaly.
 *
 * Model ini menggunakan `HasApiTokens` dari Laravel Sanctum sebagai
 * mekanisme autentikasi API berbasis token. Seluruh pengguna memiliki
 * satu peran (`role_id`) yang menentukan hak akses di sistem.
 *
 * Kolom `is_active` berfungsi sebagai soft-disable: akun yang dinonaktifkan
 * tidak dapat login meskipun kredensialnya benar, tanpa perlu dihapus dari DB.
 *
 * @property int         $id
 * @property string      $name
 * @property string      $email
 * @property string      $password    Di-hash otomatis via cast 'hashed'.
 * @property int         $role_id
 * @property bool        $is_active
 * @property \Carbon\Carbon $created_at
 *
 * @see \App\Models\Role
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Field yang dapat diisi secara massal.
     *
     * `password` di-hash otomatis oleh cast `hashed` saat diisi;
     * tidak perlu memanggil `bcrypt()` secara eksplisit.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    /**
     * Field yang disembunyikan dari serialisasi JSON (response API).
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Mendefinisikan cast tipe data untuk atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password'  => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Peran (Role) yang dimiliki pengguna ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Role, User>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    /**
     * Artikel-artikel yang ditulis oleh pengguna ini sebagai author.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Post>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    /**
     * File media yang diunggah oleh pengguna ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Media>
     */
    public function medias(): HasMany
    {
        return $this->hasMany(Media::class, 'uploader_id');
    }

    /**
     * Riwayat transaksi pembayaran yang terasosiasi dengan akun pengguna ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Payment>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'user_id');
    }
}
