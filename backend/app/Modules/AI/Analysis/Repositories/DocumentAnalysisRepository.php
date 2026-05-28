<?php

namespace App\Modules\AI\Analysis\Repositories;

use App\Modules\AI\Analysis\DTOs\AnalysisResult;
use App\Modules\AI\Analysis\Repositories\Contracts\IDocumentAnalysisRepository;
use App\Modules\Documents\Models\DocumentAnalysis;

class DocumentAnalysisRepository implements IDocumentAnalysisRepository
{
    public function upsert(string $documentId, AnalysisResult $result): DocumentAnalysis
    {
        /** @var DocumentAnalysis $analysis */
        $analysis = DocumentAnalysis::updateOrCreate(
            ['document_id' => $documentId],
            [
                'summary'       => $result->summary,
                'key_points'    => $result->keyPoints,
                'parties'       => $result->parties,
                'governing_law' => $result->governingLaw,
                'risk_score'    => $result->riskScore,
                'confidence'    => $result->confidence,
                'ai_model'      => $result->model,
                'raw_response'  => $result->rawResponse,
                'analyzed_at'   => now(),
            ]
        );

        return $analysis;
    }
}
