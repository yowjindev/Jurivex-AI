<?php

namespace App\Modules\AI\Analysis\Repositories\Contracts;

use App\Modules\AI\Analysis\DTOs\AnalysisResult;
use App\Modules\Documents\Models\DocumentAnalysis;

interface IDocumentAnalysisRepository
{
    public function upsert(string $documentId, AnalysisResult $result): DocumentAnalysis;
}
