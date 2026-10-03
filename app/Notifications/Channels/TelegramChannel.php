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
 * This channel never throws. Notifications are queued with afterCommit, so a
 * failure here can no longer roll back the business action that triggered
 * it — but a thrown exception would still mark the queued job as failed for
 * a pointless retry. A failed message must stay a failed message.
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
