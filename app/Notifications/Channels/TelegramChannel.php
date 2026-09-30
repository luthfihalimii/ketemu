<?php

namespace App\Notifications\Channels;

use App\Notifications\ActivityNotification;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Delivers an ActivityNotification over the Telegram bot.
 *
 * Registered under the name "telegram" in AppServiceProvider, which keeps
 * routeNotificationForTelegram() working on the notifiable model.
 *
 * This channel never throws. NotificationSender rethrows channel failures, and
 * notifications are sent from inside the transaction that performed the
 * business action, so an exception here would roll back a successful claim or
 * handover. A failed message must stay a failed message.
 */
class TelegramChannel
{
    public function __construct(private readonly TelegramService $telegram) {}

    public function send(object $notifiable, ActivityNotification $notification): void
    {
        $chatId = $notifiable->routeNotificationFor('telegram');

        if (! filled($chatId)) {
            return;
        }

        try {
            $this->telegram->sendMessage((string) $chatId, $notification->toTelegram($notifiable));
        } catch (Throwable $exception) {
            // Catches Error as well as Exception, in case a future change to
            // the text builder or the HTTP layer throws something non-standard.
            Log::warning('Telegram channel failed silently.', [
                'event' => $notification->event,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
