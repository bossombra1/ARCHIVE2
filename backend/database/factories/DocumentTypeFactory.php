<?php

namespace Database\Factories;

use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentType>
 */
class DocumentTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => null,
            'name' => fake()->randomElement(['Contrat', 'Facture', 'Note de service', 'Rapport', 'Courrier Administratif']),
        ];
    }
}
