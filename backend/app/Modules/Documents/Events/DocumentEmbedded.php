<?php

namespace App\Modules\Documents\Events;

use App\Modules\Documents\Models\Document;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentEmbedded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Document $document,
        public readonly int      $chunkCount,
    ) {}
}
