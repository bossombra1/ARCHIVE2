<?php

namespace Database\Factories;

use App\Models\Affectation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Affectation>
 */
class AffectationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'poste_id' => null,
            'service_id' => null,
            'department_id' => null,
            'direction_id' => null,
            'is_active' => true,
            'started_at' => today()->subMonth(),
            'ended_at' => null,
        ];
    }
}
