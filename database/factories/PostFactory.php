<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id'   => User::factory(),
            'title'       => fake()->sentence(5),
            'slug'        => fake()->unique()->slug(3),
            'content'     => fake()->paragraphs(3, true),
            'excerpt'     => fake()->sentence(15),
            'status'      => fake()->randomElement(['draft', 'published', 'archived']),
            'published_at'=> now(),
        ];
    }
}
