<?php

namespace App\Modules\AI\Contracts;

use App\Modules\Documents\Models\Document;

interface AIServiceContract
{
    public function analyze(Document $document): void;
}
