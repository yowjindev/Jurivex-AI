<?php

namespace App\Modules\Organizations\Models;

use App\Modules\Compliance\Models\ComplianceFlag;
use App\Modules\Documents\Models\Document;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'slug'];

    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function complianceFlags(): HasMany
    {
        return $this->hasMany(ComplianceFlag::class);
    }

    public function invitationCodes(): HasMany
    {
        return $this->hasMany(InvitationCode::class);
    }

    protected static function newFactory(): OrganizationFactory
    {
        return OrganizationFactory::new();
    }
}
