<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumni extends Model
{
    use HasFactory;
    protected $table = 'alumni';
    protected $primaryKey = 'alumni_id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'graduation_year',
        'photo',
        'current_institution',
        'major',
        'achievement',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'created_at'   => 'datetime',
    ];
}
