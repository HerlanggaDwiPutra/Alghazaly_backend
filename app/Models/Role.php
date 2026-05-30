<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Merepresentasikan peran (Role) yang menentukan hak akses pengguna di sistem.
 *
 * Sistem RBAC (Role-Based Access Control) Al Ghazaly berbasis pada tabel `roles`
 * ini. Setiap {@see \App\Models\User} memiliki satu peran (`role_id`).
 *
 * Peran yang umum dikonfigurasi di sistem ini antara lain:
 * - **Super Admin**: Akses penuh ke semua fitur termasuk manajemen pengguna.
 * - **Staf/Admin**: Akses ke konten dan PPDB, tapi tidak ke manajemen pengguna.
 *
 * Model ini tidak memiliki timestamps karena data peran bersifat statis
 * dan jarang berubah setelah setup awal.
 *
 * @property int    $role_id
 * @property string $name         Nama peran (contoh: 'Super Admin', 'Staf').
 * @property string $description  Deskripsi hak akses peran ini.
 */
class Role extends Model
{
    use HasFactory;

    /** @var string Primary key non-standar (bukan 'id'). */
    protected $primaryKey = 'role_id';

    /** @var bool Data peran bersifat konfigurasi tetap; tidak perlu tracking timestamps. */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Seluruh pengguna yang memiliki peran ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id', 'role_id');
    }
}
