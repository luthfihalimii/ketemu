<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use Database\Factories\ClaimFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'item_id',
    'user_id',
    'status',
    'is_verified',
    'attempt_count',
    'rejection_reason',
    'verified_at',
    'rejected_at',
    'completed_at',
])]
class Claim extends Model
{
    /** @use HasFactory<ClaimFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ClaimStatus::class,
            'is_verified' => 'boolean',
            'attempt_count' => 'integer',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Item, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasOne<PickupCode, $this> */
    public function pickupCode(): HasOne
    {
        return $this->hasOne(PickupCode::class)->latestOfMany();
    }

    /** @return HasMany<PickupCode, $this> */
    public function pickupCodes(): HasMany
    {
        return $this->hasMany(PickupCode::class);
    }

    /** @param  Builder<Claim>  $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', [
            ClaimStatus::Submitted->value,
            ClaimStatus::Approved->value,
        ]);
    }

    public function isApproved(): bool
    {
        return $this->status === ClaimStatus::Approved;
    }
}
