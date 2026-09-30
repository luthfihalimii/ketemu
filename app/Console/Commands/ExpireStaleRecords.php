<?php

namespace App\Console\Commands;

use App\Enums\ItemStatus;
use App\Enums\PickupCodeStatus;
use App\Models\Item;
use App\Models\PickupCode;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class ExpireStaleRecords extends Command
{
    protected $signature = 'ketemupens:expire';

    protected $description = 'Kedaluwarsakan kode pengambilan, barang temuan yang lama tidak diambil, dan tandai penitipan yang belum dikonfirmasi.';

    public function handle(AuditLogger $audit): int
    {
        $codes = $this->expirePickupCodes();
        $items = $this->expireStaleItems($audit);
        $reminders = $this->flagDepositFollowUps($audit);

        $this->info("Kode pengambilan kedaluwarsa: {$codes}. Barang kedaluwarsa: {$items}. Perlu tindak lanjut penitipan: {$reminders}.");

        return self::SUCCESS;
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
                    body: '"'.$item->title.'" dilaporkan '.$item->created_at?->diffInDays(now()).' hari lalu oleh '.($item->user?->name ?? 'pengguna').' tapi penitipan ke satpam belum dikonfirmasi.',
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
