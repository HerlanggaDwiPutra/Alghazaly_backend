<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Merepresentasikan artikel, berita, atau pengumuman yang diterbitkan sekolah.
 *
 * Model ini mendukung fitur SEO bawaan melalui field `meta_title`,
 * `meta_description`, dan `meta_keywords`. Slug bersifat URL-friendly
 * dan digunakan sebagai identifier di endpoint publik (bukan ID numerik)
 * untuk URL yang lebih deskriptif.
 *
 * Sistem kategori menggunakan relasi many-to-many melalui tabel pivot
 * `post_categories`, memungkinkan satu artikel berada di banyak kategori.
 *
 * @property int             $post_id
 * @property int             $author_id
 * @property string          $title
 * @property string          $slug            URL-friendly, di-generate dari title.
 * @property string|null     $excerpt         Ringkasan singkat untuk preview artikel.
 * @property string          $content         Isi artikel lengkap (HTML/Markdown).
 * @property string|null     $thumbnail       Path/URL gambar thumbnail.
 * @property string          $status          Nilai: draft|published|archived.
 * @property \Carbon\Carbon|null $published_at Waktu pertama kali artikel dipublikasikan.
 * @property string|null     $meta_title
 * @property string|null     $meta_description
 * @property string|null     $meta_keywords
 */
class Post extends Model
{
    use HasFactory;

    /** @var string Primary key non-standar (bukan 'id'). */
    protected $primaryKey = 'post_id';

    /** @var list<string> */
    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'thumbnail',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Pengguna admin yang menulis artikel ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Post>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Kategori-kategori yang mengelompokkan artikel ini (many-to-many).
     * Pivot table: `post_categories` (post_id, category_id).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Category>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'post_categories', 'post_id', 'category_id');
    }
}
