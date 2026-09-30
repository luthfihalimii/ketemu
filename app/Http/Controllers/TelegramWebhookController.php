<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Receives bot updates from Telegram.
 *
 * Telegram POSTs here with a shared secret in a header, so the route is
 * unauthenticated but not open: a request without the secret is rejected.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramService $telegram): Response
    {
        $secret = config('services.telegram.webhook_secret');

        if (! filled($secret)
            || ! hash_equals((string) $secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            abort(403);
        }

        $text = (string) data_get($request->all(), 'message.text', '');
        $chatId = data_get($request->all(), 'message.chat.id');

        // Ignore anything that is not a link attempt.
        if ($chatId === null || ! str_starts_with($text, '/start')) {
            return response()->noContent();
        }

        $token = trim(Str::after($text, '/start'));

        if ($token === '') {
            $telegram->sendMessage(
                (string) $chatId,
                'Buka KETEMU PENS, masuk ke Pengaturan, lalu tekan "Hubungkan Telegram" untuk mendapatkan tautan.',
            );

            return response()->noContent();
        }

        $user = $telegram->linkChat($token, (string) $chatId);

        $telegram->sendMessage(
            (string) $chatId,
            $user !== null
                ? 'Berhasil terhubung ke akun '.$user->name.'. Notifikasi KETEMU PENS akan dikirim ke sini.'
                : 'Tautan tidak valid atau sudah kedaluwarsa. Minta tautan baru dari halaman Pengaturan.',
        );

        return response()->noContent();
    }
}
