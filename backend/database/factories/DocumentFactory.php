<?php
namespace Database\Factories;

use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'organization_id'   => Organization::factory(),
            'uploaded_by'       => User::factory(),
            'title'             => fake()->sentence(4),
            'original_filename' => fake()->word() . '.pdf',
            'mime_type'         => 'application/pdf',
            'file_size'         => fake()->numberBetween(10240, 5 * 1024 * 1024),
            's3_path'           => 'org/' . fake()->uuid() . '/documents/' . fake()->uuid() . '/document.pdf',
            'status'            => Document::STATUS_PENDING,
            'category'          => null,
            'tags'              => null,
        ];
    }

    public function analyzed(): static
    {
        return $this->state(['status' => Document::STATUS_ANALYZED]);
    }
}
