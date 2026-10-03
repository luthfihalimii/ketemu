<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Telegram bot integration used as the external notification channel.
 *
 * Telegram cannot address an email or a phone number, so a student must link
 * their account once by pressing /start on the bot. Until they do, they only
 * receive the in-app notification.
 */
class TelegramService
{
    /**
     * How long a link token stays usable. Short, because it is the only thing
     * proving the person pressing /start controls the account.
     */
    public const TOKEN_TTL_MINUTES = 15;

    private const API_BASE = 'https://api.telegram.org';

    public function isConfigured(): bool
    {
        return filled(config('services.telegram.bot_token'))
            && $this->botUsername() !== null;
    }

    public function botUsername(): ?string
    {
        $username = config('services.telegram.bot_username');

        return filled($username) ? ltrim((string) $username, '@') : null;
    }

    /**
     * Issue a fresh one-time link token and return the deep link that opens
     * the bot with it. Returns null when Telegram is not configured.
     *
     * Only the hash is stored, so a leaked database cannot be used to link an
     * attacker's chat to someone else's account.
     */
    public function issueLink(User $user): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $plain = Str::random(48);

        $user->forceFill([
            'telegram_link_token' => hash('sha256', $plain),
            'telegram_link_token_expires_at' => now()->addMinutes(self::TOKEN_TTL_MINUTES),
        ])->save();

        return 'https://t.me/'.$this->botUsername().'?start='.$plain;
    }

    /**
     * Consume a /start token and bind the chat to its owner.
     *
     * The token is cleared on success, so a link cannot be replayed.
     */
    public function linkChat(string $plainToken, string $chatId): ?User
    {
        if ($plainToken === '') {
            return null;
        }

        $user = User::query()
            ->where('telegram_link_token', hash('sha256', $plainToken))
            ->where('telegram_link_token_expires_at', '>', now())
            ->first();

        if ($user === null) {
            return null;
        }

        $user->forceFill([
            'telegram_chat_id' => $chatId,
            'telegram_linked_at' => now(),
            'telegram_link_token' => null,
            'telegram_link_token_expires_at' => null,
        ])->save();

        return $user;
    }

    public function unlink(User $user): void
    {
        $user->forceFill([
            'telegram_chat_id' => null,
            'telegram_linked_at' => null,
            'telegram_link_token' => null,
            'telegram_link_token_expires_at' => null,
        ])->save();
    }

    /**
     * Deliver a plain-text message.
     *
     * Never throws. Notifications are queued (ShouldQueue + afterCommit), so
     * this HTTP call runs on the worker after the business transaction has
     * committed; a slow or failing Telegram API can no longer delay or roll
     * back a successful claim or handover. A failed message is only logged.
     */
    public function sendMessage(string $chatId, string $text): bool
    {
        $token = config('services.telegram.bot_token');

        if (! filled($token)) {
            return false;
        }

        try {
            $response = Http::timeout(5)
                ->asJson()
                ->post(self::API_BASE.'/bot'.$token.'/sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'disable_web_page_preview' => true,
                ]);
        } catch (Throwable $exception) {
            Log::warning('Telegram message could not be sent.', [
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('Telegram API rejected the message.', [
                'status' => $response->status(),
            ]);

            return false;
        }

        return true;
    }
}
