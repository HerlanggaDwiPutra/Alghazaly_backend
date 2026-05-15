<?php

namespace Database\Factories;

use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_number' => 'PPDB-' . date('Y') . '-' . strtoupper(Str::random(6)),
            'full_name'           => fake()->name(),
            'birth_date'          => fake()->date('Y-m-d', '-10 years'),
            'birth_place'         => fake()->city(),
            'gender'              => fake()->randomElement(['L', 'P']),
            'address'             => fake()->address(),
            'phone'               => fake()->phoneNumber(),
            'parent_name'         => fake()->name(),
            'parent_phone'        => fake()->phoneNumber(),
            'previous_school'     => 'SMP ' . fake()->company(),
            'academic_year'       => '2024/2025',
            'status'              => 'pending',
        ];
    }
}
