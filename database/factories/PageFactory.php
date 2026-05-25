<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
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
            'title'            => $title,
            'slug'             => Str::slug($title),
            'content'          => fake()->paragraphs(3, true),
            'thumbnail'        => 'uploads/pages/' . fake()->uuid() . '.jpg',
            'is_published'     => true,
            'order'            => fake()->numberBetween(0, 10),
            'meta_title'       => fake()->sentence(5),
            'meta_description' => fake()->sentence(10),
        ];
    }
}
