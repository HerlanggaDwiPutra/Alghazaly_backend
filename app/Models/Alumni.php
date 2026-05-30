<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Merepresentasikan profil alumni SMA Al Ghazaly untuk halaman galeri alumni.
 *
 * Hanya alumni dengan `is_published = true` yang ditampilkan di halaman publik.
 * Model ini menggunakan nama tabel eksplisit `alumni` (bukan `alumnis` yang
 * merupakan pluralisasi otomatis Laravel yang tidak tepat).
 *
 * @property int         $alumni_id
 * @property string      $name
 * @property int         $graduation_year  Tahun kelulusan, format 4 digit (contoh: 2023).
 * @property string|null $photo            Path/URL foto alumni.
 * @property string|null $current_institution  Institusi/universitas tempat alumni berkuliah/bekerja.
 * @property string|null $major            Jurusan/bidang studi alumni saat ini.
 * @property string|null $achievement      Prestasi atau pencapaian alumni yang layak dipublikasikan.
 * @property bool        $is_published     Jika false, profil hanya terlihat di panel admin.
 */
class Alumni extends Model
{
    use HasFactory;

    /**
     * Nama tabel ditentukan eksplisit karena pluralisasi otomatis Laravel
     * akan menghasilkan 'alumnis' yang tidak sesuai konvensi bahasa Indonesia.
     *
     * @var string
     */
    protected $table = 'alumni';

    /** @var string Primary key non-standar (bukan 'id'). */
    protected $primaryKey = 'alumni_id';

    /** @var bool Tidak ada updated_at; profil alumni jarang diperbarui secara masif. */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'graduation_year',
        'photo',
        'current_institution',
        'major',
        'achievement',
        'is_published',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_published' => 'boolean',
        'created_at'   => 'datetime',
    ];
}
