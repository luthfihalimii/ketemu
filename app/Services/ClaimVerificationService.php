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

class ClaimVerificationService
{
    /**
     * Maximum verification attempts before a claim is rejected.
     */
    public const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly PickupCodeService $pickupCodes,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Submit a verification answer and, when correct, approve the claim and
     * issue a pickup code in a single transaction.
     *
     * @return array{claim: Claim, plain_code: string|null}
     *
     * @throws ValidationException
     */
    public function submit(Item $item, User $claimant, string $answer): array
    {
        return DB::transaction(function () use ($item, $claimant, $answer) {
            $locked = Item::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->status->acceptsClaims()) {
                throw ValidationException::withMessages([
                    'answer' => 'Barang ini sedang tidak dapat diklaim.',
                ]);
            }

            if ($locked->user_id === $claimant->id) {
                throw ValidationException::withMessages([
                    'answer' => 'Kamu tidak dapat mengklaim barang yang kamu laporkan sendiri.',
                ]);
            }

            $claim = $locked->claims()->firstOrNew(['user_id' => $claimant->id]);

            if ($claim->exists && $claim->status === ClaimStatus::Completed) {
                throw ValidationException::withMessages([
                    'answer' => 'Klaim untuk barang ini sudah selesai diproses.',
                ]);
            }

            if ($claim->exists && ($claim->status === ClaimStatus::Rejected || $claim->attempt_count >= self::MAX_ATTEMPTS)) {
                throw ValidationException::withMessages([
                    'answer' => 'Batas percobaan verifikasi untuk barang ini sudah habis.',
                ]);
            }

            $attempts = $claim->attempt_count + 1;
            $correct = $locked->verifyAnswer($answer);

            $claim->fill([
                'item_id' => $locked->id,
                'status' => $correct ? ClaimStatus::Approved : ClaimStatus::Submitted,
                'is_verified' => $correct,
                'attempt_count' => $attempts,
                'verified_at' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            $plainCode = null;

            if ($correct) {
                $claim->verified_at = now();
                $claim->save();

                $this->advanceToReadyForPickup($locked);

                $issued = $this->pickupCodes->issue($claim);
                $plainCode = $issued['plain'];

                $this->audit->log(
                    event: 'claim.verified',
                    description: 'Klaim berhasil diverifikasi.',
                    auditable: $locked,
                    properties: ['claim_id' => $claim->id, 'pickup_code_id' => $issued['code']->id],
                    user: $claimant,
                );

                $claimant->notify(new ActivityNotification(
                    event: ActivityNotification::VERIFIED,
                    title: 'Klaim disetujui',
                    body: 'Klaimmu untuk "'.$locked->title.'" disetujui. Kode pengambilan sudah terbit, tunjukkan ke satpam untuk mengambil barang.',
                    item: $locked,
                    url: route('claims.pickup', $claim),
                    level: 'success',
                ));
            } else {
                if ($attempts >= self::MAX_ATTEMPTS) {
                    $claim->status = ClaimStatus::Rejected;
                    $claim->rejected_at = now();
                    $claim->rejection_reason = 'Batas percobaan verifikasi tercapai.';
                }

                $claim->save();

                $this->audit->log(
                    event: 'claim.failed',
                    description: 'Percobaan verifikasi klaim gagal.',
                    auditable: $locked,
                    properties: ['claim_id' => $claim->id, 'attempt' => $attempts],
                    user: $claimant,
                );

                $claimant->notify(new ActivityNotification(
                    event: ActivityNotification::FAILED,
                    title: $claim->status === ClaimStatus::Rejected
                        ? 'Klaim ditolak'
                        : 'Jawaban verifikasi belum tepat',
                    body: $claim->status === ClaimStatus::Rejected
                        ? 'Batas percobaan verifikasi untuk "'.$locked->title.'" sudah habis. Hubungi admin bila barang itu benar milikmu.'
                        : 'Jawabanmu belum cocok untuk "'.$locked->title.'". Sisa '.max(0, self::MAX_ATTEMPTS - $attempts).' percobaan lagi.',
                    item: $locked,
                    url: route('items.show', $locked),
                    level: $claim->status === ClaimStatus::Rejected ? 'error' : 'warning',
                ));

                // A claim that ran out of attempts is the borderline case that
                // needs a human: the real owner may simply not remember the
                // finder's answer.
                if ($claim->status === ClaimStatus::Rejected) {
                    Notification::send(User::admins(), new ActivityNotification(
                        event: ActivityNotification::ATTEMPTS_EXHAUSTED,
                        title: 'Klaim kehabisan percobaan verifikasi',
                        body: 'Klaim oleh '.$claimant->name.' untuk "'.$locked->title.'" ditolak otomatis setelah '.self::MAX_ATTEMPTS.' percobaan.',
                        item: $locked,
                        url: route('admin.items.show', $locked),
                        level: 'warning',
                    ));
                }
            }

            return ['claim' => $claim->refresh(), 'plain_code' => $plainCode];
        });
    }

    /**
     * Move a stored item through the documented status chain once its owner is
     * verified. Kept explicit so the audit trail mirrors the domain flow.
     */
    private function advanceToReadyForPickup(Item $item): void
    {
        // The item may already be exactly where it needs to be; only walk the
        // chain as far as the current status allows.
        foreach ([ItemStatus::Claimed, ItemStatus::Verified, ItemStatus::ReadyForPickup] as $status) {
            if ($item->status === $status) {
                continue;
            }

            if (! $item->status->canTransitionTo($status)) {
                continue;
            }

            $item->setStatus($status);
        }

        $item->save();
    }

    /**
     * Cancel a stale claim and return the item to the shelf.
     */
    public function release(Claim $claim): void
    {
        DB::transaction(function () use ($claim) {
            $claim->pickupCode()
                ->where('status', PickupCodeStatus::Active->value)
                ->update(['status' => PickupCodeStatus::Cancelled->value]);

            $claim->update([
                'status' => ClaimStatus::Cancelled,
                'is_verified' => false,
            ]);

            $item = $claim->item()->lockForUpdate()->first();

            if ($item !== null && $item->status->canTransitionTo(ItemStatus::Stored)) {
                $item->setStatus(ItemStatus::Stored);
                $item->save();
            }
        });
    }
}
