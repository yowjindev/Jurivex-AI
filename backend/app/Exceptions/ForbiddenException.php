<?php

namespace App\Exceptions;

class ForbiddenException extends AppException
{
    public function __construct(string $message = 'Access denied.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}
