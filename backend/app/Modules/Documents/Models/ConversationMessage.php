<?php

namespace App\Modules\Documents\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    use HasUuids;

    const ROLE_USER      = 'user';
    const ROLE_ASSISTANT = 'assistant';
    const UPDATED_AT     = null;   // append-only

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'cited_chunks',
        'prompt_tokens',
        'completion_tokens',
    ];

    protected function casts(): array
    {
        return [
            'cited_chunks'      => 'array',
            'prompt_tokens'     => 'integer',
            'completion_tokens' => 'integer',
            'created_at'        => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static fn (self $m) => $m->created_at ??= now());
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(DocumentConversation::class, 'conversation_id');
    }
}
