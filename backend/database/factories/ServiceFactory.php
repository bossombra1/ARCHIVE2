<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => null,
            'department_id' => null,
            'name' => 'Service ' . fake()->word(),
        ];
    }
}
