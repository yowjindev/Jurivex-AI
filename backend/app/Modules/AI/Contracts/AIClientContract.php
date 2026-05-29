<?php

namespace App\Modules\AI\Contracts;

use App\Modules\AI\DTOs\AIResponse;

interface AIClientContract
{
    public function complete(string $prompt): AIResponse;
}
