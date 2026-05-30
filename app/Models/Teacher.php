<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Merepresentasikan profil guru/tenaga pengajar untuk halaman profil sekolah.
 *
 * Field `order` digunakan untuk pengurutan manual oleh admin, berbeda dari
 * kebanyakan model yang diurut berdasarkan waktu input. Nilai lebih kecil
 * berarti posisi lebih awal dalam daftar tampilan.
 *
 * @property int         $teacher_id
 * @property string      $name
 * @property string|null $photo     Path/URL ke foto profil guru.
 * @property string      $position  Jabatan/posisi (contoh: 'Kepala Sekolah', 'Wali Kelas').
 * @property string|null $subject   Mata pelajaran yang diampu.
 * @property string|null $bio       Biografi singkat guru.
 * @property int         $order     Urutan tampil manual; nilai lebih kecil = tampil lebih awal.
 * @property bool        $is_active Jika false, profil disembunyikan dari halaman publik.
 */
class Teacher extends Model
{
    use HasFactory;

    /** @var string Primary key non-standar (bukan 'id'). */
    protected $primaryKey = 'teacher_id';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'photo',
        'position',
        'subject',
        'bio',
        'order',
        'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
