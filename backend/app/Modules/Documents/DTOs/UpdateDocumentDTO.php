<?php
namespace App\Modules\Documents\DTOs;

readonly class UpdateDocumentDTO
{
    public function __construct(
        public ?string $title    = null,
        public ?string $category = null,
        public ?array  $tags     = null,
    ) {}
}
