<?php

namespace App\Modules\Compliance\Repositories;

use App\Modules\AI\Risk\DTOs\RiskFlagResult;
use App\Modules\Compliance\Models\ComplianceFlag;
use App\Modules\Compliance\Repositories\Contracts\IComplianceFlagRepository;
use App\Modules\Documents\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ComplianceFlagRepository implements IComplianceFlagRepository
{
    public function listByOrganization(string $organizationId, int $perPage = 15, ?string $documentId = null): LengthAwarePaginator
    {
        $query = ComplianceFlag::where('organization_id', $organizationId);

        if ($documentId !== null) {
            $query->where('document_id', $documentId);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findById(string $id, string $organizationId): ?ComplianceFlag
    {
        return ComplianceFlag::where('id', $id)
            ->where('organization_id', $organizationId)
            ->first();
    }

    public function resolve(ComplianceFlag $flag): ComplianceFlag
    {
        $flag->update(['is_resolved' => true]);
        return $flag->fresh();
    }

    public function createFromAI(Document $document, RiskFlagResult $flag): ComplianceFlag
    {
        return ComplianceFlag::create([
            'organization_id' => $document->organization_id,
            'document_id'     => $document->id,
            'type'            => $flag->type->value,
            'severity'        => $flag->severity,
            'title'           => $flag->title,
            'description'     => $flag->description,
            'is_resolved'     => false,
            'ai_generated'    => true,
            'confidence'      => $flag->confidence,
            'source'          => ComplianceFlag::SOURCE_AI,
            'explanation'     => $flag->explanation,
        ]);
    }
}
