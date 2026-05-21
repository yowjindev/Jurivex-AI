<?php
namespace App\Modules\Documents\DTOs;

readonly class UploadDocumentDTO
{
    public function __construct(
        public string  $title,
        public string  $originalFilename,
        public string  $mimeType,
        public int     $fileSize,
        public string  $s3Path,
        public string  $organizationId,
        public string  $uploadedBy,
        public ?string $category = null,
    ) {}
}
