<?php

namespace Database\Factories;

use App\Enums\PickupCodeStatus;
use App\Models\Claim;
use App\Models\Item;
use App\Models\PickupCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PickupCode>
 */
class PickupCodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'claim_id' => Claim::factory(),
            'item_id' => fn (array $attributes) => Claim::query()->find($attributes['claim_id'])?->item_id
                ?? Item::factory()->create()->id,
            'user_id' => User::factory(),
            'code_hash' => hash('sha256', 'TESTCODE'),
            'code_hint' => 'CODE',
            'status' => PickupCodeStatus::Active,
            'expires_at' => now()->addDays(3),
        ];
    }
}
