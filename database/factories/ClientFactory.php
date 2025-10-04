<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'surname' => fake()->lastName(),
            'middle_name' => fake()->firstName(),
            'passport' => fake()->randomNumber(9),
            'place_of_issue' => 'Tashkent',
            'date_of_issue' => fake()->date(),
            'file_passport' => 'resume.png',
            'date_of_birth' => fake()->date(),
            'gender' => rand(0,1),
            'place_of_birth' => 'Tashkent',
            'place_of_residence' => 'Tashkent',
            'workplace' => 'Aloqabank',
            'specialization' => 'Kassir',
            'family_status' => 'uylanmagan',
            'number_of_children' => rand(0,3),
            // 'phones' => fake()->randomNumber(9),
            'email' => fake()->unique()->safeEmail(),
            'file' => 'resume.png',
            'bail_name' =>  fake()->name(),
            'bail_phone' => fake()->phoneNumber(),
        ];
    }
}
