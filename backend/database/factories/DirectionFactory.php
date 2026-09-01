<?php

namespace Database\Factories;

use App\Models\Direction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Direction>
 */
class DirectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => null, // à fournir à l'appel
            'name' => 'Direction ' . fake()->word(),
        ];
    }
}
