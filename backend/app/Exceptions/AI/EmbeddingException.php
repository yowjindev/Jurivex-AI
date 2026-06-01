<?php

namespace App\Exceptions\AI;

use App\Exceptions\AppException;

class EmbeddingException extends AppException
{
    public function __construct(string $message = 'Embedding generation failed.')
    {
        parent::__construct($message, 500);
    }
}
