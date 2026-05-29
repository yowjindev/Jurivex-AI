<?php

namespace App\Modules\AI\OCR\Models;

use App\Modules\Documents\Models\Document;
use Database\Factories\DocumentExtractionChunkFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentExtractionChunk extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const ANALYSIS_STATUS_PENDING = 'pending';
    public const ANALYSIS_STATUS_PROCESSING = 'processing';
    public const ANALYSIS_STATUS_COMPLETED = 'completed';
    public const ANALYSIS_STATUS_FAILED = 'failed';

    protected $fillable = [
        'document_id',
        'chunk_index',
        'page_start',
        'page_end',
        'status',
        'extracted_text',
        'word_count',
        'char_count',
        'extractor_type',
        'confidence',
        'error_message',
        'processed_at',
        'analysis_status',
        'analysis_summary',
        'analysis_key_points',
        'analysis_parties',
        'analysis_governing_law',
        'analysis_effective_date',
        'analysis_risk_score',
        'analysis_confidence',
        'analysis_model',
        'analysis_raw_response',
        'analysis_error_message',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'page_start'   => 'integer',
            'page_end'     => 'integer',
            'word_count'   => 'integer',
            'char_count'   => 'integer',
            'confidence'   => 'decimal:4',
            'processed_at' => 'datetime',
            'analysis_key_points' => 'array',
            'analysis_parties' => 'array',
            'analysis_risk_score' => 'decimal:4',
            'analysis_confidence' => 'decimal:4',
            'analyzed_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    protected static function newFactory(): DocumentExtractionChunkFactory
    {
        return DocumentExtractionChunkFactory::new();
    }
}
