<?php

namespace App\Console\Commands;

use App\Enums\ClaimStatus;
use App\Enums\ItemStatus;
use App\Enums\PickupCodeStatus;
use App\Models\Claim;
use App\Models\Item;
use App\Models\PickupCode;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Services\AuditLogger;
use App\Services\ClaimVerificationService;
use App\Services\ItemPhotoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class ExpireStaleRecords extends Command
{
    protected $signature = 'ketemupens:expire';

    public function __construct(private readonly ItemPhotoService $photos)
    {
        parent::__construct();
    }

    protected $description = 'Kedaluwarsakan kode pengambilan, lepaskan klaim yang melewati masa tenggang, kedaluwarsakan barang temuan lama, dan tandai penitipan yang belum dikonfirmasi.';

    public function handle(AuditLogger $audit, ClaimVerificationService $claims): int
    {
        $codes = $this->expirePickupCodes();
        $released = $this->releaseExpiredClaims($claims);
        $items = $this->expireStaleItems($audit);
        $reminders = $this->flagDepositFollowUps($audit);
        $holds = $this->flagHoldOverdue($audit);

        $this->info("Kode pengambilan kedaluwarsa: {$codes}. Klaim dilepas: {$released}. Barang kedaluwarsa: {$items}. Perlu tindak lanjut penitipan: {$reminders}. Tenggat tahan lewat: {$holds}.");

        return self::SUCCESS;
    }

    /**
     * Penemu yang menahan melewati SLA 24 jam: ingatkan sekali + beri tahu
     * admin. deposit_reminded_at dipakai ulang sebagai penanda sekali-kirim.
     */
    private function flagHoldOverdue(AuditLogger $audit): int
    {
        $items = Item::query()
            ->holdOverdue()
            ->whereNull('deposit_reminded_at')
            ->with('user')
            ->get();

        $admins = User::admins();

        foreach ($items as $item) {
            $item->deposit_reminded_at = now();
            $item->save();

            $audit->log(
                event: 'items.hold_overdue',
                description: 'Penemu melewati tenggat penitipan; barang masih ditahan.',
                auditable: $item,
                properties: ['hold_until' => $item->hold_until?->toDateTimeString()],
                user: null,
            );

            $item->user?->notify(new ActivityNotification(
                event: ActivityNotification::DEPOSIT_UNCONFIRMED,
                title: 'Tenggat penitipan lewat',
                body: 'Kamu berjanji menitipkan "'.$item->title.'" maks. '.Item::holdMaxHours().' jam. Segera titipkan ke satpam atau tunjukkan QR titip di pos.',
                item: $item,
                url: route('items.show', $item),
                level: 'error',
            ));

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new ActivityNotification(
                    event: ActivityNotification::ADMIN_DEPOSIT_UNCONFIRMED,
                    title: 'Penemu melewati tenggat penitipan',
                    body: '"'.$item->title.'" oleh '.($item->user->name ?? 'pengguna').' belum dititipkan melewati tenggat.',
                    item: $item,
                    url: route('admin.items.show', $item),
                    level: 'error',
                ));
            }
        }

        return $items->count();
    }

    /**
     * Lepaskan klaim tersetujui yang pemiliknya tidak pernah mengambil barang.
     *
     * Setelah kode kedaluwarsa, pemilik diberi masa tenggang
     * (pickup_code.release_grace_days) untuk meminta kode baru lewat admin;
     * lewat itu, klaim dibatalkan dan barang kembali ke status STORED.
     */
    private function releaseExpiredClaims(ClaimVerificationService $claims): int
    {
        $cutoff = now()->subDays((int) config('ketemupens.pickup_code.release_grace_days'));

        $stale = Claim::query()
            ->where('status', ClaimStatus::Approved->value)
            ->whereHas('pickupCode', function ($query) use ($cutoff) {
                $query->where('status', PickupCodeStatus::Expired->value)
                    ->where('expires_at', '<=', $cutoff);
            })
            ->with(['item', 'user'])
            ->get();

        foreach ($stale as $claim) {
            $claims->release($claim);

            $claim->user?->notify(new ActivityNotification(
                event: ActivityNotification::CLAIM_AUTO_RELEASED,
                title: 'Kode pengambilan kedaluwarsa',
                body: 'Kode pengambilan untuk "'.($claim->item->title ?? 'barang').'" kedaluwarsa dan klaimmu dilepas agar barang kembali tersedia. Kalau barang itu milikmu, ajukan klaim ulang atau hubungi admin untuk kode baru.',
                item: $claim->item,
                url: route('dashboard.claims'),
                level: 'warning',
            ));
        }

        return $stale->count();
    }

    /**
     * Surface found reports whose finder never confirmed the handover.
     *
     * The report is deliberately left in WAITING_DEPOSIT. The item may already
     * be sitting at the security post, so closing it on a timer would destroy
     * the only lead that could reunite it with its owner. This only marks the
     * report so a human can chase it.
     */
    private function flagDepositFollowUps(AuditLogger $audit): int
    {
        // Overdue reports stay in the admin queue either way; deposit_reminded_at
        // only records when the system first noticed, so the hourly run does not
        // write the same audit entry again and again.
        $items = Item::query()
            ->depositOverdue()
            ->whereNull('deposit_reminded_at')
            ->with('user')
            ->get();

        // Resolved once: every overdue report alerts the same admins.
        $admins = User::admins();

        foreach ($items as $item) {
            $item->deposit_reminded_at = now();
            $item->save();

            $audit->log(
                event: 'items.deposit_follow_up',
                description: 'Barang temuan belum dikonfirmasi dititipkan; ditandai untuk ditindaklanjuti.',
                auditable: $item,
                properties: [
                    'status' => ItemStatus::WaitingDeposit->value,
                    'days_waiting' => $item->created_at?->diffInDays(now()),
                ],
                user: null,
            );

            // The finder may simply have forgotten; this is the only nudge the
            // system can send without an email channel.
            $item->user?->notify(new ActivityNotification(
                event: ActivityNotification::DEPOSIT_UNCONFIRMED,
                title: 'Penitipan belum dikonfirmasi',
                body: 'Laporan "'.$item->title.'" belum ditandai sudah dititipkan ke satpam. Kalau barangnya sudah kamu serahkan, buka laporan dan tekan "Sudah dititipkan".',
                item: $item,
                url: route('items.show', $item),
                level: 'warning',
            ));

            // No new messages are sent on later runs, but the admin queue keeps
            // showing the report until someone acts on it.
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new ActivityNotification(
                    event: ActivityNotification::ADMIN_DEPOSIT_UNCONFIRMED,
                    title: 'Barang temuan belum dikonfirmasi dititipkan',
                    body: '"'.$item->title.'" dilaporkan '.$item->created_at?->diffInDays(now()).' hari lalu oleh '.($item->user->name ?? 'pengguna').' tapi penitipan ke satpam belum dikonfirmasi.',
                    item: $item,
                    url: route('admin.items.show', $item),
                    level: 'warning',
                ));
            }
        }

        return $items->count();
    }

    private function expirePickupCodes(): int
    {
        return PickupCode::query()
            ->where('status', PickupCodeStatus::Active->value)
            ->where('expires_at', '<=', now())
            ->update(['status' => PickupCodeStatus::Expired->value]);
    }

    private function expireStaleItems(AuditLogger $audit): int
    {
        $cutoff = now()->subDays((int) config('ketemupens.expiry.stale_after_days'));

        $items = Item::query()
            ->where('status', ItemStatus::Stored->value)
            ->whereNotNull('stored_at')
            ->where('stored_at', '<=', $cutoff)
            ->get();

        $expired = 0;

        foreach ($items as $item) {
            if (! $item->status->canTransitionTo(ItemStatus::Expired)) {
                continue;
            }

            $item->setStatus(ItemStatus::Expired);
            $item->expires_at = now();

            // Barang keluar dari katalog: foto ikut dibersihkan.
            if ($item->photo_path !== null) {
                $this->photos->delete($item->photo_path);
                $item->photo_path = null;
            }

            $item->save();

            // No admin is present, so the entry is attributed to the system.
            $audit->log(
                event: 'items.expired_automatically',
                description: 'Barang kedaluwarsa otomatis karena tidak diambil.',
                auditable: $item,
                properties: [
                    'previous_status' => ItemStatus::Stored->value,
                    'new_status' => ItemStatus::Expired->value,
                ],
                user: null,
            );

            $expired++;
        }

        return $expired;
    }
}
