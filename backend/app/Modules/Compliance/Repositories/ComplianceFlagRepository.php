<?php

namespace App\Modules\Compliance\Repositories;

use App\Modules\Compliance\Models\ComplianceFlag;
use App\Modules\Compliance\Repositories\Contracts\IComplianceFlagRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ComplianceFlagRepository implements IComplianceFlagRepository
{
    public function listByOrganization(string $organizationId, int $perPage = 15): LengthAwarePaginator
    {
        return ComplianceFlag::where('organization_id', $organizationId)
            ->latest()
            ->paginate($perPage);
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
}
