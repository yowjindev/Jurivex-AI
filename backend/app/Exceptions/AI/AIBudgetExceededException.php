<?php

namespace App\Exceptions\AI;

use App\Exceptions\AppException;

class AIBudgetExceededException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            'Monthly AI token budget exceeded. Contact your administrator.',
            429,
        );
    }
}
