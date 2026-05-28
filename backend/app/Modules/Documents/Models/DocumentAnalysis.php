<?php
namespace App\Modules\Documents\Models;

use Database\Factories\DocumentAnalysisFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAnalysis extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'document_id', 'summary', 'key_points', 'parties',
        'governing_law', 'risk_score', 'confidence',
        'ai_model', 'raw_response', 'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'key_points'  => 'array',
            'parties'     => 'array',
            'risk_score'  => 'decimal:4',
            'confidence'  => 'decimal:4',
            'analyzed_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    protected static function newFactory(): DocumentAnalysisFactory
    {
        return DocumentAnalysisFactory::new();
    }
}
