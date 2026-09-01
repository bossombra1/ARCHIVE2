<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => null,
            'direction_id' => null,
            'name' => 'Département ' . fake()->word(),
        ];
    }
}
