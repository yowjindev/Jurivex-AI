<?php

namespace App\Exceptions\AI;

use App\Exceptions\AppException;

class OcrFailedException extends AppException
{
    public function __construct(string $reason = 'OCR extraction failed.')
    {
        parent::__construct($reason, 500);
    }
}
