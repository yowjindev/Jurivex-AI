<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $organization = Organization::factory()->create();

        return [
            'organization_id'   => $organization->id,
            'uploaded_by'       => User::factory()->create(['organization_id' => $organization->id])->id,
            'title'             => fake()->sentence(4),
            'original_filename' => fake()->word() . '.pdf',
            'mime_type'         => 'application/pdf',
            'file_size'         => fake()->numberBetween(1024, 10485760),
            's3_path'           => 'documents/' . fake()->uuid() . '.pdf',
            'status'            => 'pending',
            'category'          => null,
            'tags'              => null,
        ];
    }
}
