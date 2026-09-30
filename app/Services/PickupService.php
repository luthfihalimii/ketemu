<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Enums\ItemStatus;
use App\Enums\PickupCodeStatus;
use App\Models\Item;
use App\Models\PickupCode;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PickupService
{
    /**
     * Maximum wrong code entries before a code is cancelled.
     */
    public const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly PickupCodeService $pickupCodes,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Verify a pickup code at the security post and release the item.
     *
     * The code proves the right to collect; it does not prove who is holding
     * it. The guard must therefore record the identity document they checked,
     * so every handover names a recipient.
     *
     * @throws ValidationException
     */
    public function redeem(
        string $plainCode,
        User $guard,
        string $recipientIdNumber,
        string $recipientName,
    ): PickupCode {
        return DB::transaction(function () use ($plainCode, $guard, $recipientIdNumber, $recipientName) {
            $pickupCode = $this->pickupCodes->findActiveByPlainText($plainCode);

            if ($pickupCode === null) {
                $this->audit->log(
                    event: 'pickup.failed',
                    description: 'Kode pengambilan tidak ditemukan atau tidak aktif.',
                    user: $guard,
                );

                throw ValidationException::withMessages([
                    'code' => 'Kode pengambilan tidak valid atau sudah kedaluwarsa.',
                ]);
            }

            $pickupCode = PickupCode::query()
                ->whereKey($pickupCode->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $pickupCode->isUsable()) {
                throw ValidationException::withMessages([
                    'code' => 'Kode pengambilan sudah tidak dapat digunakan.',
                ]);
            }

            $item = $pickupCode->item()->lockForUpdate()->firstOrFail();

            $pickupCode->update([
                'status' => PickupCodeStatus::Used,
                'used_at' => now(),
                'verified_by' => $guard->id,
                'verified_at' => now(),
                'recipient_id_number' => $recipientIdNumber,
                'recipient_name' => $recipientName,
            ]);

            if (! in_array($item->status, [ItemStatus::Verified, ItemStatus::ReadyForPickup], true)) {
                // Throwing rolls the transaction back, so the code is not burned.
                throw ValidationException::withMessages([
                    'code' => 'Barang ini sedang tidak menunggu pengambilan.',
                ]);
            }

            $item->setStatus(ItemStatus::Returned);
            $item->save();

            // Close any lost report that was explicitly linked to this item:
            // its owner now has the belonging back.
            foreach ($item->matchedReports()->get() as $lostReport) {
                if (! $lostReport->status->canTransitionTo(ItemStatus::Returned)) {
                    continue;
                }

                $lostReport->setStatus(ItemStatus::Returned);
                $lostReport->save();

                $lostReport->user?->notify(new ActivityNotification(
                    event: ActivityNotification::ITEM_RETURNED,
                    title: 'Barangmu sudah kembali',
                    body: 'Laporan "'.$lostReport->title.'" ditutup karena barangnya sudah diambil pemilik melalui kode pengambilan.',
                    item: $lostReport,
                    url: route('items.show', $lostReport),
                    level: 'success',
                ));
            }

            $pickupCode->claim()->update([
                'status' => ClaimStatus::Completed,
                'completed_at' => now(),
            ]);

            $this->audit->log(
                event: 'pickup.completed',
                description: 'Barang diserahkan kepada pemilik.',
                auditable: $item,
                properties: [
                    'pickup_code_id' => $pickupCode->id,
                    'recipient_id_number' => $recipientIdNumber,
                    'recipient_name' => $recipientName,
                ],
                user: $guard,
            );

            return $pickupCode->refresh();
        });
    }
}
