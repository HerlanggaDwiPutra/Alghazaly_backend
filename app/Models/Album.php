<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Album extends Model
{
    use HasFactory;
    protected $primaryKey = 'album_id';

    protected $fillable = [
        'title',
        'slug',
        'cover',
        'description',
        'is_published',
        'order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function medias(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'album_medias', 'album_id', 'media_id')
                    ->withPivot('order')
                    ->orderByPivot('order');
    }
}
