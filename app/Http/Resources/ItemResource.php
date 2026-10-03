<?php

namespace App\Http\Resources;

use App\Models\Item;
use App\Services\ItemPhotoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Item
 */
class ItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'category' => $this->whenLoaded('category', fn () => ['id' => $this->category->id, 'name' => $this->category->name]),
            'location' => $this->whenLoaded('location', fn () => ['id' => $this->location->id, 'name' => $this->location->name]),
            'deposit_location' => $this->whenLoaded('depositLocation', fn () => ['id' => $this->depositLocation->id, 'name' => $this->depositLocation->name]),
            'color' => $this->color,
            'brand' => $this->brand,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'photo_url' => $this->photo_path ? app(ItemPhotoService::class)->url($this->photo_path) : null,
            // verification_answer + private_note TIDAK PERNAH diexpose ke API publik.
            'is_deposit_confirmed' => $this->isDepositConfirmed(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
