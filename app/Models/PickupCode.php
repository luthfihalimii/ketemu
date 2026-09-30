<?php

namespace App\Models;

use App\Enums\PickupCodeStatus;
use Database\Factories\PickupCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['code_hash', 'code_encrypted', 'recipient_id_number', 'recipient_name'])]
#[Fillable([
    'claim_id',
    'item_id',
    'user_id',
    'code_hash',
    'code_encrypted',
    'code_hint',
    'status',
    'attempts',
    'expires_at',
    'used_at',
    'verified_by',
    'verified_at',
    'recipient_id_number',
    'recipient_name',
])]
class PickupCode extends Model
{
    /** @use HasFactory<PickupCodeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => PickupCodeStatus::class,
            'code_encrypted' => 'encrypted',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * The plaintext code, decrypted only for the owner's own pickup page.
     */
    public function plainCode(): ?string
    {
        return $this->code_encrypted;
    }

    /** @return BelongsTo<Claim, $this> */
    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
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

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Effective status, accounting for codes whose window has passed.
     */
    public function effectiveStatus(): PickupCodeStatus
    {
        if ($this->status === PickupCodeStatus::Active && $this->expires_at->isPast()) {
            return PickupCodeStatus::Expired;
        }

        return $this->status;
    }

    public function isUsable(): bool
    {
        return $this->effectiveStatus() === PickupCodeStatus::Active;
    }
}
