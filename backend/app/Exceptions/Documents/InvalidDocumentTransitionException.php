<?php

namespace App\Exceptions\Documents;

use App\Exceptions\AppException;

class InvalidDocumentTransitionException extends AppException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct(
            "Cannot transition document from '{$from}' to '{$to}'.",
            422,
        );
    }
}
