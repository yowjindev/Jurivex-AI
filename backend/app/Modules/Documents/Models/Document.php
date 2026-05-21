<?php

namespace App\Modules\Documents\Models;

use App\Modules\Organizations\Models\Organization;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'organization_id', 'uploaded_by', 'title', 'original_filename',
        'mime_type', 'file_size', 's3_path', 'status', 'category', 'tags',
    ];

    protected function casts(): array
    {
        return [
            'tags'      => 'array',
            'file_size' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    protected static function newFactory(): DocumentFactory
    {
        return DocumentFactory::new();
    }
}
