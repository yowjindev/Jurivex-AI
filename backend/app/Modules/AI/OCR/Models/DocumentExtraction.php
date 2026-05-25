<?php

namespace App\Modules\AI\OCR\Models;

use App\Modules\Documents\Models\Document;
use Database\Factories\DocumentExtractionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentExtraction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'document_id', 'extracted_text', 'page_count', 'word_count', 'char_count',
        'ocr_engine', 'extractor_type', 'confidence', 'extracted_at',
    ];

    protected function casts(): array
    {
        return [
            'extracted_at' => 'datetime',
            'confidence'   => 'float',
            'page_count'   => 'integer',
            'word_count'   => 'integer',
            'char_count'   => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    protected static function newFactory(): DocumentExtractionFactory
    {
        return DocumentExtractionFactory::new();
    }
}
