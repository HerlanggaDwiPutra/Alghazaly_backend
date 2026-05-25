<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory;
    protected $primaryKey = 'testimonial_id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'role',
        'content',
        'photo',
        'rating',
        'is_published',
        'order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'created_at'   => 'datetime',
    ];
}
