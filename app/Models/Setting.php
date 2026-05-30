<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Merepresentasikan satu entri konfigurasi global aplikasi (key-value store).
 *
 * Sistem pengaturan Al Ghazaly menggunakan pola Entity-Attribute-Value (EAV)
 * berbasis database, memungkinkan admin mengubah konfigurasi seperti nama
 * sekolah, alamat, atau link media sosial tanpa perlu mengubah file `.env`.
 *
 * Kolom `group` digunakan untuk mengelompokkan pengaturan berdasarkan konteks
 * (contoh: `general`, `contact`, `social_media`), sehingga frontend panel admin
 * dapat merender form pengaturan dalam tab/section yang terorganisir.
 *
 * Semua nilai disimpan sebagai `string`; konversi tipe (integer, boolean, dll.)
 * dilakukan oleh consumer (controller/frontend) sesuai kebutuhan.
 *
 * Model ini menonaktifkan `timestamps` karena pengaturan adalah data konfigurasi
 * yang tidak memerlukan audit trail waktu perubahan di tingkat database.
 *
 * @property int         $setting_id
 * @property string      $key    Identifier unik pengaturan (contoh: 'school_name', 'contact_email').
 * @property string|null $value  Nilai pengaturan sebagai string (nullable jika belum dikonfigurasi).
 * @property string|null $group  Grup pengelompokan pengaturan untuk tampilan panel admin.
 */
class Setting extends Model
{
    use HasFactory;

    /** @var string Primary key non-standar (bukan 'id'). */
    protected $primaryKey = 'setting_id';

    /** @var bool Data konfigurasi tidak memerlukan tracking timestamps. */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'key',
        'value',
        'group',
    ];
}
