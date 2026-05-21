<?php

namespace App\Modules\Compliance\Models;

use App\Modules\Documents\Models\Document;
use App\Modules\Organizations\Models\Organization;
use Database\Factories\ComplianceFlagFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceFlag extends Model
{
    use HasFactory, HasUuids;

    const TYPE_RISK     = 'risk';
    const TYPE_DEADLINE = 'deadline';
    const TYPE_ALERT    = 'alert';

    const SEVERITY_LOW      = 'low';
    const SEVERITY_MEDIUM   = 'medium';
    const SEVERITY_HIGH     = 'high';
    const SEVERITY_CRITICAL = 'critical';

    protected $fillable = [
        'organization_id', 'document_id', 'type', 'severity',
        'title', 'description', 'due_date', 'is_resolved',
    ];

    protected function casts(): array
    {
        return [
            'due_date'    => 'date',
            'is_resolved' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    protected static function newFactory(): ComplianceFlagFactory
    {
        return ComplianceFlagFactory::new();
    }
}
