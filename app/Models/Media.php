<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Merepresentasikan aset file yang diunggah ke storage aplikasi (Media Library).
 *
 * Model ini bertindak sebagai repositori terpusat untuk semua aset biner:
 * gambar profil guru, foto alumni, thumbnail artikel, dan berkas PPDB.
 * Entitas lain menyimpan `media_id` atau `path` untuk mereferensikan aset ini.
 *
 * File fisik disimpan di disk `public` (storage/app/public/), yang harus
 * di-symlink ke `public/storage/` menggunakan perintah `php artisan storage:link`
 * agar dapat diakses via URL publik.
 *
 * Model ini menonaktifkan `timestamps` karena hanya memiliki kolom `created_at`.
 *
 * @property int         $media_id
 * @property int         $uploader_id  ID user admin pengunggah; 0 untuk upload publik (PPDB).
 * @property string      $filename     Nama file asli dari client.
 * @property string      $path         Path relatif di disk 'public' (contoh: `uploads/abc.jpg`).
 * @property string      $mime_type    Tipe MIME file (contoh: `image/jpeg`, `application/pdf`).
 * @property int         $size         Ukuran file dalam byte.
 * @property \Carbon\Carbon $created_at
 */
class Media extends Model
{
    use HasFactory;

    /** @var string Nama tabel non-konvensional (bukan 'media'). */
    protected $table = 'medias';

    /** @var string Primary key non-standar (bukan 'id'). */
    protected $primaryKey = 'media_id';

    /** @var bool Hanya created_at; tidak ada updated_at karena file bersifat immutable setelah upload. */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'uploader_id',
        'filename',
        'path',
        'mime_type',
        'size',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'created_at' => 'datetime',
        'size'        => 'integer',
    ];

    /**
     * Pengguna admin yang mengunggah file ini.
     *
     * Catatan: Jika `uploader_id` adalah 0 (upload publik dari PPDB),
     * relasi ini akan mengembalikan `null`.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Media>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /**
     * Berkas-berkas persyaratan PPDB yang menggunakan aset ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<RegistrationDocument>
     */
    public function registrationDocuments(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class, 'media_id', 'media_id');
    }

    /**
     * Album foto yang memuat aset media ini.
     * Kolom `order` pada pivot digunakan untuk menentukan urutan tampil foto dalam album.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Album>
     */
    public function albums(): BelongsToMany
    {
        return $this->belongsToMany(Album::class, 'album_medias', 'media_id', 'album_id')
                    ->withPivot('order');
    }
}
