<?php

namespace App\Http\Resources;

use App\Models\Claim;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Claim
 */
class ClaimResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'attempt_count' => $this->attempt_count,
            'is_verified' => (bool) $this->is_verified,
            'item' => new ItemResource($this->whenLoaded('item')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
