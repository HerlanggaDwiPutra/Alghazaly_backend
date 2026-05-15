<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;
    protected $primaryKey = 'category_id';
    public $timestamps = false;

    protected $fillable = [
        'category_name',
        'slug',
        'parent_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id', 'category_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id', 'category_id');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_categories', 'category_id', 'post_id');
    }
}
