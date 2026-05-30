<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Merepresentasikan template formulir dinamis yang dapat dikonfigurasi admin.
 *
 * Arsitektur formulir bersifat schema-driven: kolom `fields` menyimpan
 * array JSON yang mendefinisikan struktur setiap input (nama field, tipe,
 * label, dan aturan validasi). Frontend merender formulir secara dinamis
 * berdasarkan schema ini.
 *
 * Contoh struktur `fields`:
 * ```json
 * [
 *   { "name": "nama", "type": "text", "label": "Nama Lengkap", "required": true },
 *   { "name": "pesan", "type": "textarea", "label": "Pesan", "required": true }
 * ]
 * ```
 *
 * @property int        $form_id
 * @property string     $name       Nama deskriptif formulir (tampil di panel admin).
 * @property string     $slug       Identifier URL-friendly untuk endpoint publik.
 * @property array      $fields     Schema definisi field formulir (JSON).
 * @property bool       $is_active  Mengontrol aksesibilitas formulir dari publik.
 */
class Form extends Model
{
    use HasFactory;

    /** @var string Primary key non-standar (bukan 'id'). */
    protected $primaryKey = 'form_id';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'fields',
        'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'fields'    => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Seluruh data kiriman yang diterima melalui formulir ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<FormSubmission>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_id', 'form_id');
    }
}
