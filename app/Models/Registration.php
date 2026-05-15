<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Registration extends Model
{
    use HasFactory;
    protected $primaryKey = 'registration_id';

    protected $fillable = [
        'registration_number',
        'full_name',
        'birth_date',
        'birth_place',
        'gender',
        'address',
        'phone',
        'parent_name',
        'parent_phone',
        'previous_school',
        'academic_year',
        'status',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class, 'registration_id', 'registration_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'registration_id', 'registration_id');
    }
}
