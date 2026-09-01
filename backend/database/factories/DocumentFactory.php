<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => null,
            'document_type_id' => null,
            'uploaded_by' => null,
            'service_id' => null,
            'department_id' => null,
            'direction_id' => null,
            'title' => 'Document ' . fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'file_path' => 'documents/test/' . fake()->uuid() . '.pdf',
            'file_type' => 'pdf',
            'file_size' => fake()->numberBetween(1024, 1024 * 1024),
        ];
    }
}
