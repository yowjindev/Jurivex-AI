<?php

namespace App\Modules\Organizations\Models;

use App\Models\User;
use Database\Factories\InvitationCodeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationCode extends Model
{
    /** @use HasFactory<InvitationCodeFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id',
        'code',
        'role',
        'used_by',
        'used_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'used_at'    => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->isUsed() && ! $this->isExpired();
    }

    protected static function newFactory(): InvitationCodeFactory
    {
        return InvitationCodeFactory::new();
    }
}
