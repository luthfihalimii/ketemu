<?php

namespace App\Notifications;

use App\Models\Item;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app and Telegram notification for claim, pickup, match and moderation
 * activity.
 *
 * Email is deliberately not a channel yet: the mailer is still the log driver,
 * so an email would be written to the log and never reach a student.
 */
class ActivityNotification extends Notification
{
    use Queueable;

    public const VERIFIED = 'claim.verified';

    public const REJECTED = 'claim.rejected';

    public const FAILED = 'claim.failed';

    public const ITEM_RETURNED = 'item.returned';

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

        // Linked students also get a Telegram message. Anyone unlinked, or an
        // install without a bot token, silently falls back to in-app only.
        if (app(TelegramService::class)->isConfigured()
            && filled($notifiable->routeNotificationFor('telegram'))) {
            $channels[] = 'telegram';
        }

        return $channels;
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
