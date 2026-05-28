<?php

namespace App\Modules\AI\Analysis\DTOs;

readonly class AnalysisResult
{
    public function __construct(
        public string  $summary,
        public array   $keyPoints,
        public array   $parties,
        public ?string $governingLaw,
        public ?string $effectiveDate,
        public float   $riskScore,
        public float   $confidence,
        public string  $model,
        public string  $rawResponse,
    ) {}
}
