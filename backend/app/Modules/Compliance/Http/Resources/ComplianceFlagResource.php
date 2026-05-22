<?php
namespace App\Modules\Compliance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplianceFlagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'organization_id' => $this->organization_id,
            'document_id'     => $this->document_id,
            'type'            => $this->type,
            'severity'        => $this->severity,
            'title'           => $this->title,
            'description'     => $this->description,
            'due_date'        => $this->due_date?->toDateString(),
            'is_resolved'     => $this->is_resolved,
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
