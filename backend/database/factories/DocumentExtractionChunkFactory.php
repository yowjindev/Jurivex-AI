<?php

namespace Database\Factories;

use App\Modules\AI\OCR\Models\DocumentExtractionChunk;
use App\Modules\Documents\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentExtractionChunkFactory extends Factory
{
    protected $model = DocumentExtractionChunk::class;

    public function definition(): array
    {
        return [
            'document_id'    => Document::factory(),
            'chunk_index'    => 0,
            'page_start'     => 1,
            'page_end'       => 1,
            'status'         => DocumentExtractionChunk::STATUS_PENDING,
            'extracted_text' => null,
            'word_count'     => null,
            'char_count'     => null,
            'extractor_type' => null,
            'confidence'     => null,
            'error_message'  => null,
            'processed_at'   => null,
            'analysis_status' => DocumentExtractionChunk::ANALYSIS_STATUS_PENDING,
            'analysis_summary' => null,
            'analysis_key_points' => null,
            'analysis_parties' => null,
            'analysis_governing_law' => null,
            'analysis_effective_date' => null,
            'analysis_risk_score' => null,
            'analysis_confidence' => null,
            'analysis_model' => null,
            'analysis_raw_response' => null,
            'analysis_error_message' => null,
            'analyzed_at' => null,
        ];
    }
}
