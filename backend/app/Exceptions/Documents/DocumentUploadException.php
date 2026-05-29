<?php

namespace App\Exceptions\Documents;

use App\Exceptions\AppException;

class DocumentUploadException extends AppException
{
    public function __construct(string $message = 'Document upload failed.')
    {
        parent::__construct($message, 500);
    }
}
