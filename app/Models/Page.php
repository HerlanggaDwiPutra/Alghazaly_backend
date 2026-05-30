<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Merepresentasikan halaman statis konten sekolah yang dikelola melalui CMS.
 *
 * Berbeda dengan {@see \App\Models\Post} yang bersifat artikel berulang,
 * model ini merepresentasikan halaman permanen seperti "Visi & Misi",
 * "Sejarah Sekolah", atau "Fasilitas". Identifikasi halaman menggunakan
 * `slug` (bukan ID numerik) agar URL lebih deskriptif dan SEO-friendly.
 *
 * Field `order` mengontrol urutan tampil halaman dalam navigasi/menu
 * yang di-generate secara dinamis oleh frontend.
 *
 * @property int         $page_id
 * @property string      $title            Judul halaman.
 * @property string      $slug             Identifier URL-friendly (contoh: 'visi-misi').
 * @property string      $content          Isi halaman lengkap (HTML/Markdown/rich text).
 * @property string|null $thumbnail        Path/URL gambar header halaman.
 * @property bool        $is_published     Jika false, halaman hanya terlihat di panel admin.
 * @property int         $order            Urutan tampil halaman dalam navigasi.
 * @property string|null $meta_title       Judul SEO (max 160 karakter).
 * @property string|null $meta_description Deskripsi meta untuk mesin pencari (max 255 karakter).
 */
class Page extends Model
{
    use HasFactory;

    /** @var string Primary key non-standar (bukan 'id'). */
    protected $primaryKey = 'page_id';

    /** @var list<string> */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'thumbnail',
        'is_published',
        'order',
        'meta_title',
        'meta_description',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_published' => 'boolean',
    ];
}
