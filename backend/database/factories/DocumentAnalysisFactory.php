<?php
namespace Database\Factories;

use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentAnalysisFactory extends Factory
{
    protected $model = DocumentAnalysis::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'summary'     => fake()->paragraph(),
            'key_points'  => [fake()->sentence(), fake()->sentence()],
            'parties'     => ['Acme Corp', 'Widget Inc'],
            'risk_score'  => fake()->randomFloat(4, 0, 1),
            'confidence'  => fake()->randomFloat(4, 0.5, 1),
            'ai_model'    => 'gpt-4o',
            'analyzed_at' => now(),
        ];
    }
}
