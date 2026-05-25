<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'         => fake()->name(),
            'role'         => fake()->randomElement(['Orang Tua Siswa', 'Alumni', 'Siswa', 'Mitra Sekolah']),
            'content'      => fake()->paragraph(),
            'photo'        => 'uploads/testimonials/' . fake()->uuid() . '.jpg',
            'rating'       => fake()->numberBetween(1, 5),
            'is_published' => true,
            'order'        => fake()->numberBetween(0, 10),
        ];
    }
}
