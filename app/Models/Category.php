<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Merepresentasikan kategori untuk pengelompokan artikel/berita sekolah.
 *
 * Model ini mendukung struktur hierarkis (parent-child) melalui self-referential
 * relation: sebuah kategori dapat memiliki sub-kategori (`children`) dan
 * merujuk ke kategori induknya (`parent`). Nilai `parent_id = null` menandakan
 * kategori tingkat teratas (root category).
 *
 * Model ini menonaktifkan `timestamps` (tidak memiliki updated_at).
 *
 * @property int         $category_id
 * @property string      $category_name
 * @property string      $slug
 * @property int|null    $parent_id     Null jika kategori ini adalah root/induk.
 */
class Category extends Model
{
    use HasFactory;

    /** @var string Primary key non-standar (bukan 'id'). */
    protected $primaryKey = 'category_id';

    /** @var bool Tidak ada updated_at; hanya created_at. */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'category_name',
        'slug',
        'parent_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Kategori induk dari kategori ini (self-referential, nullable).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Category, Category>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id', 'category_id');
    }

    /**
     * Sub-kategori (anak) yang dimiliki oleh kategori ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Category>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id', 'category_id');
    }

    /**
     * Artikel-artikel yang termasuk dalam kategori ini (many-to-many).
     * Pivot table: `post_categories` (category_id, post_id).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Post>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_categories', 'category_id', 'post_id');
    }
}
