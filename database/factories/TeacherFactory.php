<?php

namespace Database\Factories;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'      => fake()->name(),
            'position'  => fake()->randomElement(['Guru', 'Kepala Sekolah', 'Wakil Kepala Sekolah', 'Guru BK']),
            'subject'   => fake()->randomElement(['Matematika', 'Bahasa Indonesia', 'Fisika', 'Kimia', 'Biologi']),
            'photo'     => 'uploads/teachers/' . fake()->uuid() . '.jpg',
            'bio'       => fake()->paragraph(),
            'order'     => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }
}
