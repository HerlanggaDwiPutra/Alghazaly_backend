<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Merepresentasikan pivot antara pendaftaran PPDB dan berkas persyaratan yang diunggah.
 *
 * Setiap record menghubungkan satu jenis dokumen (misalnya: "ijazah", "akta_lahir",
 * "kartu_keluarga") dengan satu aset fisik di tabel `medias`. Satu pendaftaran
 * dapat memiliki banyak dokumen dengan tipe yang berbeda.
 *
 * Model ini menonaktifkan `timestamps`; waktu upload dapat ditelusuri melalui
 * kolom `created_at` pada tabel `medias` yang terasosiasi.
 *
 * @property int    $id
 * @property int    $registration_id
 * @property string $document_type   Jenis dokumen (contoh: 'ijazah', 'akta_lahir').
 * @property int    $media_id        Referensi ke aset fisik di tabel medias.
 */
class RegistrationDocument extends Model
{
    use HasFactory;

    /** @var bool Tidak menggunakan created_at/updated_at otomatis. */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'registration_id',
        'document_type',
        'media_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    /**
     * Pendaftaran PPDB yang memiliki berkas ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Registration, RegistrationDocument>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id', 'registration_id');
    }

    /**
     * Metadata dan path file fisik dari berkas ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Media, RegistrationDocument>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id', 'media_id');
    }
}
