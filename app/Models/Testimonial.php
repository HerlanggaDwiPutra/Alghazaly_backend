<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Merepresentasikan satu testimonial (ulasan/kesaksian) dari wali murid,
 * alumni, atau tokoh masyarakat yang ditampilkan di halaman publik sekolah.
 *
 * Field `order` digunakan untuk pengurutan manual oleh admin sehingga
 * testimonial unggulan dapat diprioritaskan tanpa memandang waktu input.
 * Field `rating` menggunakan skala bintang 1–5 (nullable); jika null,
 * frontend dapat menyembunyikan komponen rating.
 *
 * Hanya testimonial dengan `is_published = true` yang ditampilkan publik
 * melalui endpoint {@see \App\Http\Controllers\Api\TestimonialController}.
 *
 * Model ini menonaktifkan `timestamps` (tidak memiliki updated_at); hanya
 * `created_at` yang dikelola sebagai referensi waktu penambahan data.
 *
 * @property int         $testimonial_id
 * @property string      $name           Nama pemberi testimonial.
 * @property string|null $role           Peran/jabatan (contoh: 'Wali Murid', 'Alumni 2020').
 * @property string      $content        Isi teks testimonial.
 * @property string|null $photo          Path/URL foto profil pemberi testimonial.
 * @property int|null    $rating         Penilaian bintang (skala 1–5), nullable.
 * @property bool        $is_published   Jika false, testimonial disembunyikan dari publik.
 * @property int         $order          Urutan tampil manual; nilai lebih kecil = prioritas lebih tinggi.
 * @property \Carbon\Carbon $created_at
 */
class Testimonial extends Model
{
    use HasFactory;

    /** @var string Primary key non-standar (bukan 'id'). */
    protected $primaryKey = 'testimonial_id';

    /** @var bool Tidak ada updated_at; testimonial bersifat statis setelah diinput. */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'role',
        'content',
        'photo',
        'rating',
        'is_published',
        'order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_published' => 'boolean',
        'created_at'   => 'datetime',
    ];
}
