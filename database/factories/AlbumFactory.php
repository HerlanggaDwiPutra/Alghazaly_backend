<?php

namespace Database\Factories;

use App\Models\Album;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Album>
 */
class AlbumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title'        => $title,
            'slug'         => Str::slug($title),
            'cover'        => 'uploads/covers/' . fake()->uuid() . '.jpg',
            'description'  => fake()->sentence(10),
            'is_published' => true,
            'order'        => fake()->numberBetween(0, 10),
        ];
    }
}
