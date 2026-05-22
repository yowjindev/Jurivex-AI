<?php

namespace App\Modules\Compliance\Events;

use App\Modules\Compliance\Models\ComplianceFlag;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplianceFlagGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly ComplianceFlag $flag) {}
}
