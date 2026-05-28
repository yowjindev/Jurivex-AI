<?php

namespace App\Modules\Compliance\Repositories\Contracts;

use App\Modules\AI\Risk\DTOs\RiskFlagResult;
use App\Modules\Compliance\Models\ComplianceFlag;
use App\Modules\Documents\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IComplianceFlagRepository
{
    public function listByOrganization(string $organizationId, int $perPage = 15, ?string $documentId = null): LengthAwarePaginator;
    public function findById(string $id, string $organizationId): ?ComplianceFlag;
    public function resolve(ComplianceFlag $flag): ComplianceFlag;
    public function createFromAI(Document $document, RiskFlagResult $flag): ComplianceFlag;
}
