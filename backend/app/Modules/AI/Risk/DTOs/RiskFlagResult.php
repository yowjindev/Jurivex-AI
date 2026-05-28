<?php

namespace App\Modules\AI\Risk\DTOs;

use App\Modules\Compliance\Enums\ComplianceFlagType;

readonly class RiskFlagResult
{
    public function __construct(
        public ComplianceFlagType $type,
        public string             $severity,
        public string             $title,
        public string             $description,
        public string             $explanation,
        public float              $confidence,
    ) {}
}
