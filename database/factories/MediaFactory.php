<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->uuid() . '.jpg';

        return [
            'uploader_id' => User::factory(),
            'filename'    => $filename,
            'path'        => 'uploads/' . $filename,
            'mime_type'   => 'image/jpeg',
            'size'        => fake()->numberBetween(10000, 5000000),
        ];
    }
}
