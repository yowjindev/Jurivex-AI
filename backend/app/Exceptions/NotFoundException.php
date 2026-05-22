<?php

namespace App\Exceptions;

class NotFoundException extends AppException
{
    public function __construct(string $message = 'Resource not found.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}
