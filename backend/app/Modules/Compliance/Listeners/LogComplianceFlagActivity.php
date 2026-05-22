<?php

namespace App\Modules\Compliance\Listeners;

use App\Modules\Compliance\Events\ComplianceFlagGenerated;

class LogComplianceFlagActivity
{
    public function handle(ComplianceFlagGenerated $event): void
    {
        // Phase 2: notify relevant users of new compliance flag
    }
}
