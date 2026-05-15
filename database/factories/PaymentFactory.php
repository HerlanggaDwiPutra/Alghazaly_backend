<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'order_id'        => 'ORDER-' . strtoupper(Str::random(8)),
            'amount'          => fake()->randomElement([500000, 750000, 1000000]),
            'currency'        => 'IDR',
            'status'          => 'pending',
        ];
    }
}
