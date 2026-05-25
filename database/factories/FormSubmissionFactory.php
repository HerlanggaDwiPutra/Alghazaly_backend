<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormSubmission>
 */
class FormSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id'         => Form::factory(),
            'data'            => [
                'nama'  => fake()->name(),
                'email' => fake()->safeEmail(),
                'pesan' => fake()->paragraph(),
            ],
            'submitter_ip'    => fake()->ipv4(),
            'submitter_email' => fake()->safeEmail(),
            'is_read'         => false,
        ];
    }
}
