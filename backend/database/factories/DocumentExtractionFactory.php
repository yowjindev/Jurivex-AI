<?php

namespace Database\Factories;

use App\Modules\AI\OCR\Models\DocumentExtraction;
use App\Modules\Documents\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentExtractionFactory extends Factory
{
    protected $model = DocumentExtraction::class;

    public function definition(): array
    {
        return [
            'document_id'    => Document::factory(),
            'extracted_text' => $this->faker->paragraphs(3, true),
            'page_count'     => $this->faker->numberBetween(1, 20),
            'word_count'     => $this->faker->numberBetween(100, 5000),
            'char_count'     => $this->faker->numberBetween(500, 30000),
            'ocr_engine'     => 'tesseract',
            'extractor_type' => $this->faker->randomElement(['pdf_text', 'pdf_ocr', 'image_ocr']),
            'confidence'     => $this->faker->randomFloat(2, 0.7, 1.0),
            'extracted_at'   => now(),
        ];
    }
}
