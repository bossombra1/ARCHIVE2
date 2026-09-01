<?php

namespace Database\Factories;

use App\Models\DocumentPermission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentPermission>
 */
class DocumentPermissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_id' => null,
            'target_type' => fake()->randomElement(['user', 'poste', 'service']),
            'target_id' => null,
            'expires_at' => null,
        ];
    }
}
