<?php
namespace App\Modules\Documents\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'title'             => $this->title,
            'original_filename' => $this->original_filename,
            'mime_type'         => $this->mime_type,
            'file_size'         => $this->file_size,
            'status'            => $this->status,
            'category'          => $this->category,
            'tags'              => $this->tags ?? [],
            'uploaded_by'       => $this->uploaded_by,
            'organization_id'   => $this->organization_id,
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
            'analysis'          => $this->whenLoaded('analysis', fn () => $this->analysis ? [
                'summary'       => $this->analysis->summary,
                'key_points'    => $this->analysis->key_points,
                'parties'       => $this->analysis->parties,
                'governing_law' => $this->analysis->governing_law,
                'risk_score'    => $this->analysis->risk_score,
                'confidence'    => $this->analysis->confidence,
                'ai_model'      => $this->analysis->ai_model,
                'analyzed_at'   => $this->analysis->analyzed_at?->toISOString(),
            ] : null),
        ];
    }
}
