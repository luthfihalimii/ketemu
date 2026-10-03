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
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PickupService
{
    /**
     * Maximum consecutive wrong code entries before the guard is locked out
     * temporarily. Wrong codes never resolve to a record (lookup is by hash),
     * so brute-force protection is tracked per guard, not per code.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * Minutes a guard stays locked out after MAX_ATTEMPTS consecutive misses.
     */
    public const LOCKOUT_MINUTES = 15;

    public function __construct(
        private readonly PickupCodeService $pickupCodes,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Mask an identity number for the audit trail, keeping only the last
     * four digits so a handover can still be cross-checked.
     */
    public static function maskIdNumber(string $idNumber): string
    {
        return str_repeat('*', max(0, strlen($idNumber) - 4)).substr($idNumber, -4);
    }

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
        $limiterKey = 'pickup-redeem-fail|'.$guard->id;

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($limiterKey);

            $this->audit->log(
                event: 'pickup.locked_out',
                description: 'Terlalu banyak kode salah berturut-turut; verifikasi dikunci sementara.',
                user: $guard,
                properties: ['retry_after_seconds' => $seconds],
            );

            throw ValidationException::withMessages([
                'code' => 'Terlalu banyak percobaan kode salah. Coba lagi dalam '.(int) ceil($seconds / 60).' menit.',
            ]);
        }

        $pickupCode = $this->pickupCodes->findActiveByPlainText($plainCode);

        if ($pickupCode === null) {
            RateLimiter::hit($limiterKey, self::LOCKOUT_MINUTES * 60);

            $this->audit->log(
                event: 'pickup.failed',
                description: 'Kode pengambilan tidak ditemukan atau tidak aktif.',
                user: $guard,
            );

            throw ValidationException::withMessages([
                'code' => 'Kode pengambilan tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        return DB::transaction(function () use ($pickupCode, $guard, $recipientIdNumber, $recipientName, $limiterKey) {
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
            foreach ($item->matchedReports()->where('user_id', $pickupCode->user_id)->with('user')->lockForUpdate()->get() as $lostReport) {
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

            // Serah-terima berhasil: reset penghitung tebakan salah petugas.
            RateLimiter::clear($limiterKey);

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
                    // Nomor identitas utuh hanya disimpan terenkripsi di
                    // pickup_codes; audit log cukup menyimpan bentuk tersamar.
                    'recipient_id_number_masked' => self::maskIdNumber($recipientIdNumber),
                    'recipient_name' => $recipientName,
                ],
                user: $guard,
            );

            return $pickupCode->refresh();
        });
    }
}
