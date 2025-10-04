<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'imei' => fake()->randomNumber(),
            'model' => Str::random(10),
            'provider' => fake()->name(),
            'account' => fake()->safeEmail(),
            'incoming_price' => fake()->randomNumber(),
        ];
    }
}
