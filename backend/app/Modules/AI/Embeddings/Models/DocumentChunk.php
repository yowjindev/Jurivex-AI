<?php

namespace App\Modules\AI\Embeddings\Models;

use App\Modules\Documents\Models\Document;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentChunk extends Model
{
    use HasUuids;

    protected $fillable = [
        'document_id',
        'organization_id',
        'chunk_index',
        'text',
        'token_count',
        'embedding_model',
        'embedded_at',
    ];

    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
            'token_count' => 'integer',
            'embedded_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
