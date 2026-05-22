<?php

namespace App\Exceptions;

use RuntimeException;

abstract class AppException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 500,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function toArray(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
        ];
    }
}
