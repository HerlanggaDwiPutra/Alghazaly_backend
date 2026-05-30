<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Merepresentasikan album foto untuk galeri visual sekolah Al Ghazaly.
 *
 * Setiap album adalah kumpulan aset {@see \App\Models\Media} yang dihubungkan
 * melalui tabel pivot `album_medias`. Kolom `order` pada pivot menentukan
 * urutan tampil foto di dalam album (ascending), memungkinkan admin menyusun
 * foto secara manual terlepas dari urutan upload.
 *
 * Album dengan `is_published = false` hanya terlihat di panel admin
 * dan tidak muncul di endpoint publik.
 *
 * @property int         $album_id
 * @property string      $title          Judul album foto.
 * @property string      $slug           Identifier URL-friendly untuk endpoint publik.
 * @property string|null $cover          Path/URL ke gambar sampul album.
 * @property string|null $description    Deskripsi singkat isi album.
 * @property bool        $is_published   Kontrol visibilitas publik album.
 * @property int         $order          Urutan tampil manual album dalam daftar galeri.
 */
class Album extends Model
{
    use HasFactory;

    /** @var string Primary key non-standar (bukan 'id'). */
    protected $primaryKey = 'album_id';

    /** @var list<string> */
    protected $fillable = [
        'title',
        'slug',
        'cover',
        'description',
        'is_published',
        'order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_published' => 'boolean',
    ];

    /**
     * Seluruh aset media yang tergabung dalam album ini (many-to-many).
     *
     * Pivot table `album_medias` menyimpan kolom `order` tambahan yang
     * digunakan untuk mengurutkan foto dalam album secara manual.
     * `orderByPivot('order')` memastikan foto selalu tampil sesuai urutan
     * yang ditetapkan admin, bukan berdasarkan waktu upload atau media_id.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Media>
     */
    public function medias(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'album_medias', 'album_id', 'media_id')
                    ->withPivot('order')
                    ->orderByPivot('order');
    }
}
