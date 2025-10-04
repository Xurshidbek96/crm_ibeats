<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->lastName(),
            'surname' => fake()->lastName(),
            'passport' => fake()->randomNumber(9),
            'place_of_issue' => 'Tashkent',
            'date_of_issue' => fake()->date(),
            'date_of_birth' => fake()->date(),
            'gender' => rand(0,1),
            'place_of_birth' => 'Tashkent',
            'place_of_residence' => 'Tashkent',
            'family_status' => 'uylanmagan',
            'number_of_children' => rand(0,3),
            'phone1' => fake()->unique()->phoneNumber(),
            'phone2' => fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('admin12345'), // password
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
