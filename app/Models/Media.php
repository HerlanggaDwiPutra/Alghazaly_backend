<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Media extends Model
{
    use HasFactory;

    protected $table = 'medias';
    protected $primaryKey = 'media_id';
    public $timestamps = false;

    protected $fillable = [
        'uploader_id',
        'filename',
        'path',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'size'        => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function registrationDocuments(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class, 'media_id', 'media_id');
    }

    public function albums(): BelongsToMany
    {
        return $this->belongsToMany(Album::class, 'album_medias', 'media_id', 'album_id')
                    ->withPivot('order');
    }
}
