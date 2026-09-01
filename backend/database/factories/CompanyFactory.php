<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'size' => fake()->randomElement(['small', 'large']),
            'logo_path' => null,
            'is_configured' => true,
        ];
    }
}
