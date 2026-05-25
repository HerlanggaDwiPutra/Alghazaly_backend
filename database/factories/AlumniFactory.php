<?php

namespace Database\Factories;

use App\Models\Alumni;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alumni>
 */
class AlumniFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'                => fake()->name(),
            'graduation_year'     => fake()->numberBetween(2018, 2025),
            'photo'               => 'uploads/alumni/' . fake()->uuid() . '.jpg',
            'current_institution' => fake()->company(),
            'major'               => fake()->randomElement(['IPA', 'IPS', 'Bahasa', 'Teknik Informatika']),
            'achievement'         => fake()->sentence(8),
            'is_published'        => true,
        ];
    }
}
