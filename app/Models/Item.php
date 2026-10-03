<?php

namespace App\Models;

use App\Enums\ItemStatus;
use App\Exceptions\InvalidMatchException;
use App\Exceptions\InvalidStatusTransitionException;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Hidden(['verification_answer', 'private_note'])]
#[Fillable([
    'user_id',
    'category_id',
    'title',
    'description',
    'private_note',
    'location_id',
    'location_detail',
    'occurred_at',
    'color',
    'brand',
    'photo_path',
    'deposit_location_id',
    'deposit_note',
    'matched_item_id',
    'deposit_reminded_at',
    'deposit_requested_at',
    'deposit_confirmed_at',
    'deposit_confirmed_by',
    'verification_question',
    'verification_answer',
])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ItemStatus::class,
            'occurred_at' => 'datetime',
            'stored_at' => 'datetime',
            'returned_at' => 'datetime',
            'expires_at' => 'datetime',
            'flagged_at' => 'datetime',
            'moderated_at' => 'datetime',
            'deposit_reminded_at' => 'datetime',
            'deposit_requested_at' => 'datetime',
            'deposit_confirmed_at' => 'datetime',
            // Hashing the answer means a leaked database never reveals the secret.
            'verification_answer' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Item $item) {
            $item->code ??= self::generateCode();
        });
    }

    public static function generateCode(): string
    {
        return 'KP-'.Str::upper((string) Str::uuid());
    }

    /**
     * Gunakan kode laporan (KP-UUID) untuk route model binding agar URL
     * detail tidak bisa di-enumerasi berurutan (/items/1, /items/2, ...).
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function depositLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'deposit_location_id');
    }

    /**
     * The found item this lost report turned out to refer to.
     *
     * @return BelongsTo<Item, $this>
     */
    public function matchedItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'matched_item_id');
    }

    /**
     * Lost reports this found item has been linked to.
     *
     * @return HasMany<Item, $this>
     */
    public function matchedReports(): HasMany
    {
        return $this->hasMany(Item::class, 'matched_item_id');
    }

    /** @return HasMany<Claim, $this> */
    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    /** @return HasMany<PickupCode, $this> */
    public function pickupCodes(): HasMany
    {
        return $this->hasMany(PickupCode::class);
    }

    /** @return BelongsTo<User, $this> */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /** @return BelongsTo<User, $this> */
    public function depositConfirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deposit_confirmed_by');
    }

    public function isDepositConfirmed(): bool
    {
        return $this->deposit_confirmed_at !== null;
    }

    #[Scope]
    protected function flagged(Builder $query): void
    {
        $query->whereNotNull('flagged_at');
    }

    public function isFlagged(): bool
    {
        return $this->flagged_at !== null;
    }

    /**
     * Found reports whose finder has not confirmed the handover within the
     * reminder window. Reminded or not, these still need a human to chase.
     */
    #[Scope]
    protected function depositOverdue(Builder $query): void
    {
        $query->where('status', ItemStatus::WaitingDeposit->value)
            ->where('created_at', '<=', now()->subDays(self::depositReminderDays()));
    }

    public function isDepositOverdue(): bool
    {
        return $this->status === ItemStatus::WaitingDeposit
            && $this->created_at !== null
            && $this->created_at->lte(now()->subDays(self::depositReminderDays()));
    }

    public static function depositReminderDays(): int
    {
        return (int) config('ketemupens.expiry.deposit_reminder_after_days');
    }

    /**
     * Items that should be visible to the wider campus (stored onward).
     *
     * @param  Builder<Item>  $query
     */
    #[Scope]
    protected function discoverable(Builder $query): void
    {
        $query->whereIn('status', [
            ItemStatus::Stored->value,
            ItemStatus::Claimed->value,
            ItemStatus::Verified->value,
            ItemStatus::ReadyForPickup->value,
        ]);
    }

    public function isPubliclyAvailable(): bool
    {
        return $this->status->isPubliclyAvailable();
    }

    /**
     * A lost report never carries a deposit location; a found report always
     * does. This is the same rule ModerationService::restore() already uses,
     * made explicit so the two flows cannot be confused.
     */
    public function isLostReport(): bool
    {
        return $this->deposit_location_id === null;
    }

    public function isFoundReport(): bool
    {
        return ! $this->isLostReport();
    }

    /**
     * Whether this lost report has been linked to a found item.
     */
    public function isMatched(): bool
    {
        return $this->matched_item_id !== null;
    }

    /**
     * Link this lost report to the found item that turned out to be the same
     * belonging. Claiming still uses the finder's verification answer; the
     * link only records that the two reports describe one item.
     *
     * @throws InvalidMatchException
     */
    public function matchTo(Item $found): void
    {
        if (! $this->isLostReport()) {
            throw InvalidMatchException::notALostReport();
        }

        if (! $found->isFoundReport()) {
            throw InvalidMatchException::notAFoundReport();
        }

        if ($this->is($found)) {
            throw InvalidMatchException::selfMatch();
        }

        $this->matched_item_id = $found->getKey();
        $this->save();
    }

    public function unmatch(): void
    {
        $this->matched_item_id = null;
        $this->save();
    }

    /**
     * Apply a guarded status change.
     *
     * @throws InvalidStatusTransitionException
     */
    public function setStatus(ItemStatus $status): void
    {
        $this->status = $this->status->transitionTo($status);

        if ($this->status === ItemStatus::Stored && $this->stored_at === null) {
            $this->stored_at = now();
        }

        if ($this->status === ItemStatus::Returned) {
            $this->returned_at = now();
        }
    }

    public function hasVerification(): bool
    {
        return filled($this->verification_answer);
    }

    /**
     * Check a submitted verification answer against the stored hash.
     */
    public function verifyAnswer(?string $answer): bool
    {
        if (! $this->hasVerification() || blank($answer)) {
            return false;
        }

        // Normalise exactly as when the answer was stored, otherwise answers
        // with irregular spacing would never match.
        return password_verify(
            self::normalizeAnswer($answer),
            $this->verification_answer,
        );
    }

    /**
     * Normalise a plaintext verification answer before hashing or comparing.
     */
    public static function normalizeAnswer(string $answer): string
    {
        return Str::lower(Str::squish(trim($answer)));
    }
}
