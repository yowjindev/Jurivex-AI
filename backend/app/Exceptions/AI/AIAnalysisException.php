<?php

namespace App\Exceptions\AI;

use App\Exceptions\AppException;

class AIAnalysisException extends AppException
{
    public function __construct(string $reason)
    {
        parent::__construct($reason, 500);
    }
}
