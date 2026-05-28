<?php

namespace App\Modules\Compliance\Enums;

enum ComplianceFlagType: string
{
    case Risk     = 'risk';
    case Deadline = 'deadline';
    case Alert    = 'alert';
    case Other    = 'other';

    public static function fromAI(string $value): self
    {
        return self::tryFrom(strtolower(trim($value))) ?? self::Other;
    }
}
