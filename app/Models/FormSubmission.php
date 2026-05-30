<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Merepresentasikan satu kiriman data dari sebuah formulir dinamis.
 *
 * Kolom `data` menyimpan jawaban pengguna sebagai array JSON bebas-struktur
 * yang sesuai dengan schema `fields` pada model {@see \App\Models\Form}.
 * Tidak ada validasi per-field di sisi server; integritas data bergantung
 * pada validasi di sisi frontend.
 *
 * Model ini menonaktifkan `timestamps` karena hanya memiliki kolom `created_at`
 * (tanpa `updated_at`); kiriman formulir bersifat immutable setelah disimpan.
 *
 * @property int         $submission_id
 * @property int         $form_id
 * @property array       $data            Jawaban pengguna (key-value JSON bebas).
 * @property string|null $submitter_ip    IP pengirim untuk keperluan audit/anti-spam.
 * @property string|null $submitter_email Email pengirim (opsional, dari input formulir).
 * @property bool        $is_read         Status baca; diubah ke true saat admin membuka detail.
 * @property \Carbon\Carbon $created_at
 */
class FormSubmission extends Model
{
    use HasFactory;

    /** @var string Primary key non-standar (bukan 'id'). */
    protected $primaryKey = 'submission_id';

    /**
     * Hanya created_at yang dikelola; tidak ada updated_at karena kiriman bersifat immutable.
     *
     * @var bool
     */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'form_id',
        'data',
        'submitter_ip',
        'submitter_email',
        'is_read',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'data'       => 'array',
        'is_read'    => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Formulir asal dari kiriman data ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Form, FormSubmission>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id', 'form_id');
    }
}
