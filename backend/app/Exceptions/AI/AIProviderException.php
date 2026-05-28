<?php

namespace App\Exceptions\AI;

use App\Exceptions\AppException;

class AIProviderException extends AppException
{
    public function __construct(string $reason)
    {
        parent::__construct($reason, 503);
    }
}
