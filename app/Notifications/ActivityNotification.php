<?php

namespace App\Notifications;

use App\Models\Item;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * In-app, email and Telegram notification for claim, pickup, match and
 * moderation activity.
 *
 * Queued so a slow mailer or Telegram API never delays (or rolls back) the
 * business transaction that triggered the notification.
 */
class ActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Only deliver after the surrounding transaction commits; otherwise a
     * rollback could leave a notification for something that never happened.
     */
    public function afterCommit(): bool
    {
        return true;
    }

    public const VERIFIED = 'claim.verified';

    public const REJECTED = 'claim.rejected';

    public const FAILED = 'claim.failed';

    public const ITEM_RETURNED = 'item.returned';

    public const CODE_REISSUED = 'claim.code_reissued';

    public const CLAIM_AUTO_RELEASED = 'claim.auto_released';

    public const DEPOSIT_UNCONFIRMED = 'item.deposit_unconfirmed';

    // Admin-facing events.
    public const ITEM_FLAGGED = 'admin.item_flagged';

    public const ATTEMPTS_EXHAUSTED = 'admin.claim_attempts_exhausted';

    public const ADMIN_DEPOSIT_UNCONFIRMED = 'admin.deposit_unconfirmed';

    public function __construct(
        public readonly string $event,
        public readonly string $title,
        public readonly string $body,
        public readonly ?Item $item = null,
        public readonly ?string $url = null,
        public readonly string $level = 'info',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Email hanya didaftarkan bila mailer sungguhan aktif; dengan driver
        // log/array, email tidak akan pernah sampai ke mahasiswa.
        if (! in_array((string) config('mail.default'), ['log', 'array'], true)) {
            $channels[] = 'mail';
        }

        // Linked students also get a Telegram message. Anyone unlinked, or an
        // install without a bot token, silently falls back to in-app only.
        if (app(TelegramService::class)->isConfigured()
            && filled($notifiable->routeNotificationFor('telegram'))) {
            $channels[] = 'telegram';
        }

        return $channels;
    }

    /**
     * Plain-text email version of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->title)
            ->line($this->body);

        if ($this->url !== null) {
            $message->action('Buka KETEMU PENS', url($this->url));
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title,
            'body' => $this->body,
            'level' => $this->level,
            'item_id' => $this->item?->id,
            'item_title' => $this->item?->title,
            'url' => $this->url,
        ];
    }

    /**
     * Plain text for the Telegram bot. Telegram uses its own markup, so the
     * message is kept free of HTML.
     */
    public function toTelegram(object $notifiable): string
    {
        return $this->title."\n\n".$this->body;
    }
}
