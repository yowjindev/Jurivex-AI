<?php

namespace App\Exceptions\Documents;

use App\Exceptions\NotFoundException;

class DocumentNotFoundException extends NotFoundException
{
    public function __construct()
    {
        parent::__construct('Document not found.');
    }
}
