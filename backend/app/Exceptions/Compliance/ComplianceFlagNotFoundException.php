<?php

namespace App\Exceptions\Compliance;

use App\Exceptions\NotFoundException;

class ComplianceFlagNotFoundException extends NotFoundException
{
    public function __construct()
    {
        parent::__construct('Compliance flag not found.');
    }
}
