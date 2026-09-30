<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Enums\ItemStatus;
use App\Enums\PickupCodeStatus;
use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ModerationService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly PickupCodeService $pickupCodes,
    ) {}

    /**
     * Mark a report as suspicious without changing its status.
     */
    public function flag(Item $item, string $reason, User $admin): Item
    {
        $item->update([
            'flagged_at' => now(),
            'flag_reason' => $reason,
            'moderated_by' => $admin->id,
            'moderated_at' => now(),
        ]);

        $this->audit->log(
            event: 'moderation.item_flagged',
            description: 'Laporan ditandai mencurigakan.',
            auditable: $item,
            properties: ['reason' => $reason],
            user: $admin,
        );

        // Every admin, not just the one who flagged it: someone has to decide
        // what happens to the report.
        Notification::send(User::admins(), new ActivityNotification(
            event: ActivityNotification::ITEM_FLAGGED,
            title: 'Laporan ditandai mencurigakan',
            body: '"'.$item->title.'" ditandai oleh '.$admin->name.'. Alasan: '.$reason,
            item: $item,
            url: route('admin.items.show', $item),
            level: 'warning',
        ));

        return $item;
    }

    public function unflag(Item $item, User $admin): Item
    {
        $item->update([
            'flagged_at' => null,
            'flag_reason' => null,
            'moderated_by' => $admin->id,
            'moderated_at' => now(),
        ]);

        $this->audit->log(
            event: 'moderation.item_unflagged',
            description: 'Tanda mencurigakan pada laporan dihapus.',
            auditable: $item,
            user: $admin,
        );

        return $item;
    }

    /**
     * Take a report down (fake, inappropriate, or abusive). Active claims are
     * cancelled and any live pickup code is invalidated.
     */
    public function reject(Item $item, string $reason, User $admin): Item
    {
        return DB::transaction(function () use ($item, $reason, $admin) {
            $item = Item::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            $previous = $item->status;

            if (! $previous->canTransitionTo(ItemStatus::Rejected)) {
                throw ValidationException::withMessages([
                    'reason' => "Laporan berstatus {$previous->label()} tidak dapat dinonaktifkan.",
                ]);
            }

            $item->status = $previous->transitionTo(ItemStatus::Rejected);
            $item->moderation_note = $reason;
            $item->moderated_by = $admin->id;
            $item->moderated_at = now();
            $item->save();

            $this->cancelActiveClaims($item, $reason);

            $this->audit->log(
                event: 'moderation.item_rejected',
                description: 'Laporan dinonaktifkan oleh admin.',
                auditable: $item,
                properties: [
                    'reason' => $reason,
                    'previous_status' => $previous->value,
                    'new_status' => $item->status->value,
                ],
                user: $admin,
            );

            $item->user?->notify(new ActivityNotification(
                event: ActivityNotification::REJECTED,
                title: 'Laporan dinonaktifkan',
                body: 'Laporan "'.$item->title.'" dinonaktifkan oleh admin. Alasan: '.$reason,
                item: $item,
                url: route('items.show', $item),
                level: 'error',
            ));

            return $item;
        });
    }

    /**
     * Administrative reversal: put a rejected report back on the shelf.
     */
    public function restore(Item $item, User $admin): Item
    {
        return DB::transaction(function () use ($item, $admin) {
            $item = Item::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            $previous = $item->status;

            if ($previous !== ItemStatus::Rejected) {
                throw ValidationException::withMessages([
                    'reason' => 'Hanya laporan yang ditolak yang dapat dipulihkan.',
                ]);
            }

            // Found reports go back to the shelf; lost reports go back to REPORTED.
            $target = $item->isFoundReport()
                ? ItemStatus::Stored
                : ItemStatus::Reported;

            $item->status = $previous->transitionTo($target);
            $item->moderation_note = null;
            $item->moderated_by = $admin->id;
            $item->moderated_at = now();
            $item->save();

            $this->audit->log(
                event: 'moderation.item_restored',
                description: 'Laporan yang ditolak dipulihkan oleh admin.',
                auditable: $item,
                properties: [
                    'previous_status' => $previous->value,
                    'new_status' => $item->status->value,
                ],
                user: $admin,
            );

            return $item;
        });
    }

    /**
     * Reject a specific claim and return the item to the shelf.
     */
    public function rejectClaim(Claim $claim, string $reason, User $admin): Claim
    {
        return DB::transaction(function () use ($claim, $reason, $admin) {
            $claim = Claim::query()->whereKey($claim->getKey())->lockForUpdate()->firstOrFail();
            $previous = $claim->status;

            if (! $previous->isActive()) {
                throw ValidationException::withMessages([
                    'reason' => 'Klaim ini sudah tidak aktif.',
                ]);
            }

            $claim->pickupCode()
                ->where('status', PickupCodeStatus::Active->value)
                ->update(['status' => PickupCodeStatus::Cancelled->value]);

            $claim->update([
                'status' => ClaimStatus::Rejected,
                'is_verified' => false,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $item = $claim->item()->lockForUpdate()->first();

            if ($item !== null && $item->status->canTransitionTo(ItemStatus::Stored)) {
                $item->setStatus(ItemStatus::Stored);
                $item->moderated_by = $admin->id;
                $item->moderated_at = now();
                $item->save();
            }

            $this->audit->log(
                event: 'moderation.claim_rejected',
                description: 'Klaim ditolak oleh admin.',
                auditable: $item ?? $claim,
                properties: [
                    'claim_id' => $claim->id,
                    'reason' => $reason,
                    'previous_status' => $previous->value,
                    'new_status' => $claim->status->value,
                ],
                user: $admin,
            );

            $claim->user?->notify(new ActivityNotification(
                event: ActivityNotification::REJECTED,
                title: 'Klaim ditolak',
                body: 'Klaimmu untuk "'.($item?->title ?? 'barang').'" ditolak admin. Alasan: '.$reason,
                item: $item,
                url: $item !== null ? route('items.show', $item) : route('dashboard.claims'),
                level: 'error',
            ));

            return $claim->refresh();
        });
    }

    /**
     * Reissue a pickup code for an approved claim whose code expired.
     *
     * @return array{claim: Claim, plain: string}
     */
    public function reissueCode(Claim $claim, User $admin): array
    {
        return DB::transaction(function () use ($claim, $admin) {
            $claim = Claim::query()->whereKey($claim->getKey())->lockForUpdate()->firstOrFail();

            if ($claim->status !== ClaimStatus::Approved) {
                throw ValidationException::withMessages([
                    'reason' => 'Hanya klaim yang sudah terverifikasi yang dapat diterbitkan kode baru.',
                ]);
            }

            $issued = $this->pickupCodes->issue($claim);

            $this->audit->log(
                event: 'moderation.code_reissued',
                description: 'Kode pengambilan diterbitkan ulang oleh admin.',
                auditable: $claim->item,
                properties: ['claim_id' => $claim->id, 'pickup_code_id' => $issued['code']->id],
                user: $admin,
            );

            return ['claim' => $claim->refresh(), 'plain' => $issued['plain']];
        });
    }

    /**
     * Expire a stale stored item so it drops out of the public catalogue.
     */
    public function expire(Item $item, User $admin): Item
    {
        return DB::transaction(function () use ($item, $admin) {
            $item = Item::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            $previous = $item->status;

            if (! $previous->canTransitionTo(ItemStatus::Expired)) {
                throw ValidationException::withMessages([
                    'reason' => "Laporan berstatus {$previous->label()} tidak dapat dikedaluwarsakan.",
                ]);
            }

            $item->status = $previous->transitionTo(ItemStatus::Expired);
            $item->expires_at = now();
            $item->moderated_by = $admin->id;
            $item->moderated_at = now();
            $item->save();

            $this->audit->log(
                event: 'moderation.item_expired',
                description: 'Laporan dikedaluwarsakan oleh admin.',
                auditable: $item,
                properties: [
                    'previous_status' => $previous->value,
                    'new_status' => $item->status->value,
                ],
                user: $admin,
            );

            return $item;
        });
    }

    /**
     * Cancel every active claim and live code attached to a taken-down item.
     */
    private function cancelActiveClaims(Item $item, string $reason): void
    {
        $claims = $item->claims()
            ->whereIn('status', [ClaimStatus::Submitted->value, ClaimStatus::Approved->value])
            ->lockForUpdate()
            ->get();

        foreach ($claims as $claim) {
            $claim->pickupCode()
                ->where('status', PickupCodeStatus::Active->value)
                ->update(['status' => PickupCodeStatus::Cancelled->value]);

            $claim->update([
                'status' => ClaimStatus::Cancelled,
                'is_verified' => false,
                'rejection_reason' => $reason,
            ]);
        }
    }
}
