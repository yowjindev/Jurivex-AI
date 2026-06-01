<?php

namespace App\Modules\Documents\Services;

use App\Exceptions\Documents\DocumentNotFoundException;
use App\Exceptions\Documents\DocumentUploadException;
use App\Exceptions\ForbiddenException;
use App\Models\User;
use App\Modules\Auth\Models\AuditLog;
use App\Modules\Documents\DTOs\UpdateDocumentDTO;
use App\Modules\Documents\DTOs\UploadDocumentDTO;
use App\Modules\Documents\Events\DocumentUploaded;
use App\Modules\Documents\Jobs\ProcessDocumentJob;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Repositories\Contracts\IDocumentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    public function __construct(private readonly IDocumentRepository $documentRepository) {}

    public function list(User $user): LengthAwarePaginator
    {
        return $this->documentRepository->listForUser($user);
    }

    public function show(string $id, User $user): Document
    {
        $document = $this->documentRepository->findById($id, $user->organization_id);

        if ($document === null) {
            throw new DocumentNotFoundException();
        }

        if ($user->hasRole('staff') && $document->uploaded_by !== $user->id) {
            throw new ForbiddenException('Access denied.');
        }

        return $document;
    }

    public function upload(UploadedFile $file, User $user, ?string $category = null): Document
    {
        $uuid     = Str::uuid()->toString();
        $filename = $file->getClientOriginalName();

        $disk = Storage::disk('s3');
        $path = $disk->putFileAs(
            "org/{$user->organization_id}/documents/{$uuid}",
            $file,
            $filename
        );

        if ($path === false) {
            throw new DocumentUploadException('Document upload failed while writing to storage.');
        }

        $dto = new UploadDocumentDTO(
            title:            pathinfo($filename, PATHINFO_FILENAME),
            originalFilename: $filename,
            mimeType:         $file->getMimeType() ?? $file->getClientMimeType(),
            fileSize:         $file->getSize(),
            s3Path:           $path,
            organizationId:   $user->organization_id,
            uploadedBy:       $user->id,
            category:         $category,
        );

        $document = $this->documentRepository->create([
            'organization_id'   => $dto->organizationId,
            'uploaded_by'       => $dto->uploadedBy,
            'title'             => $dto->title,
            'original_filename' => $dto->originalFilename,
            'mime_type'         => $dto->mimeType,
            'file_size'         => $dto->fileSize,
            's3_path'           => $dto->s3Path,
            'status'            => Document::STATUS_PENDING,
            'category'          => $dto->category,
        ]);

        AuditLog::create([
            'organization_id' => $user->organization_id,
            'user_id'         => $user->id,
            'action'          => 'document.uploaded',
            'auditable_type'  => 'document',
            'auditable_id'    => $document->id,
            'new_values'      => ['title' => $document->title, 'filename' => $document->original_filename],
            'metadata'        => ['mime_type' => $document->mime_type, 'file_size' => $document->file_size],
        ]);

        DocumentUploaded::dispatch($document);
        ProcessDocumentJob::dispatch($document);

        return $document;
    }

    public function update(Document $document, UpdateDocumentDTO $dto): Document
    {
        $data = array_filter(
            ['title' => $dto->title, 'category' => $dto->category, 'tags' => $dto->tags],
            fn ($v) => $v !== null
        );

        return $this->documentRepository->update($document, $data);
    }

    public function delete(Document $document, User $user): void
    {
        if (! $user->hasAnyRole(['admin', 'manager', 'superadmin'])) {
            throw new ForbiddenException('Only admins, managers, and superadmins can delete documents.');
        }

        AuditLog::create([
            'organization_id' => $user->organization_id,
            'user_id'         => $user->id,
            'action'          => 'document.deleted',
            'auditable_type'  => 'document',
            'auditable_id'    => $document->id,
            'new_values'      => ['title' => $document->title],
            'metadata'        => ['file_size' => $document->file_size, 'original_filename' => $document->original_filename],
        ]);

        $this->documentRepository->delete($document);
    }

    public function retry(Document $document, User $user): Document
    {
        if ($document->status !== Document::STATUS_FAILED) {
            throw new \App\Exceptions\ForbiddenException('Only failed documents can be retried.');
        }

        $statusManager = app(DocumentStatusManager::class);
        $statusManager->transition($document, Document::STATUS_PENDING);

        AuditLog::create([
            'organization_id' => $user->organization_id,
            'user_id'         => $user->id,
            'action'          => 'document.retried',
            'auditable_type'  => 'document',
            'auditable_id'    => $document->id,
            'new_values'      => ['status' => Document::STATUS_PENDING],
            'metadata'        => [],
        ]);

        ProcessDocumentJob::dispatch($document);

        return $document->fresh();
    }

    public function downloadUrl(Document $document): ?string
    {
        try {
            return Storage::disk('s3')->temporaryUrl($document->s3_path, now()->addMinutes(60));
        } catch (\League\Flysystem\UnableToGenerateTemporaryUrl) {
            return null;
        }
    }

    public function latestFailureReason(Document $document): ?string
    {
        /** @var AuditLog|null $log */
        $log = AuditLog::query()
            ->where('action', 'ocr.failed')
            ->where('auditable_type', 'document')
            ->where('auditable_id', $document->id)
            ->latest('created_at')
            ->first();

        return $log?->metadata['reason'] ?? null;
    }
}
