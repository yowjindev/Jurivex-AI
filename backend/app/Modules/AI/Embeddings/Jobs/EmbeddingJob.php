<?php

namespace App\Modules\AI\Embeddings\Jobs;

use App\Exceptions\AI\EmbeddingException;
use App\Modules\AI\Contracts\EmbeddingClientContract;
use App\Modules\AI\Embeddings\Repositories\Contracts\IDocumentChunkRepository;
use App\Modules\AI\Embeddings\Services\ChunkingService;
use App\Modules\Documents\Events\DocumentEmbedded;
use App\Modules\Documents\Events\DocumentEmbeddingFailed;
use App\Modules\Documents\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class EmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 3;
    public $timeout = 60;

    public function __construct(public readonly Document $document)
    {
        $this->onConnection('redis')->onQueue('embeddings');
    }

    public function handle(): void
    {
        $client  = app(EmbeddingClientContract::class);
        $chunker = app(ChunkingService::class);
        $repo    = app(IDocumentChunkRepository::class);

        $extraction = $this->document->extraction;

        if (! filled($extraction?->extracted_text)) {
            throw new EmbeddingException(
                "Document {$this->document->id} has no extracted text to embed."
            );
        }

        $chunks     = $chunker->chunk($extraction->extracted_text);
        $embeddings = [];

        foreach ($chunks as $chunk) {
            $embeddings[] = $client->embed($chunk->text);
        }

        $repo->upsertChunks(
            $this->document->id,
            $this->document->organization_id,
            $chunks,
            $embeddings,
            $client->getModel(),
        );

        DocumentEmbedded::dispatch($this->document, count($chunks));
    }

    public function failed(Throwable $e): void
    {
        DocumentEmbeddingFailed::dispatch($this->document, $e->getMessage());
    }
}
