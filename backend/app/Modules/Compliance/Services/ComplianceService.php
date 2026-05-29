<?php

namespace App\Modules\Compliance\Services;

use App\Exceptions\Compliance\ComplianceFlagNotFoundException;
use App\Models\User;
use App\Modules\Auth\Models\AuditLog;
use App\Modules\Compliance\Models\ComplianceFlag;
use App\Modules\Compliance\Repositories\Contracts\IComplianceFlagRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ComplianceService
{
    public function __construct(private readonly IComplianceFlagRepository $repository) {}

    public function list(User $user, ?string $documentId = null): LengthAwarePaginator
    {
        return $this->repository->listByOrganization($user->organization_id, 15, $documentId);
    }

    public function resolve(string $id, User $user): ComplianceFlag
    {
        $flag = $this->repository->findById($id, $user->organization_id);

        if ($flag === null) {
            throw new ComplianceFlagNotFoundException();
        }

        $resolved = $this->repository->resolve($flag);

        AuditLog::create([
            'organization_id' => $user->organization_id,
            'user_id'         => $user->id,
            'action'          => 'flag.resolved',
            'auditable_type'  => 'compliance_flag',
            'auditable_id'    => $flag->id,
            'new_values'      => ['title' => $flag->title],
            'metadata'        => ['severity' => $flag->severity, 'type' => $flag->type],
        ]);

        return $resolved;
    }
}
