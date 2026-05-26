<?php

namespace App\Exceptions\AI;

use App\Exceptions\AppException;

class UnsupportedMimeTypeException extends AppException
{
    public function __construct(string $mimeType)
    {
        parent::__construct("Unsupported MIME type for OCR: {$mimeType}", 422);
    }
}
