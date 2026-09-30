<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lets a student connect their Telegram account, which is the only external
 * notification channel the project has.
 */
class TelegramController extends Controller
{
    public function __construct(private readonly TelegramService $telegram) {}

    public function show(Request $request): View
    {
        $user = $request->user();

        // A fresh link is issued on every visit; the previous one is replaced,
        // so there is never more than one usable token per account.
        $linkUrl = $this->telegram->isConfigured() ? $this->telegram->issueLink($user) : null;

        return view('settings.telegram', [
            'linkUrl' => $linkUrl,
            'configured' => $this->telegram->isConfigured(),
            'linkedAt' => $user->telegram_linked_at,
            'chatId' => $user->telegram_chat_id,
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->telegram->unlink($request->user());

        return back()->with('status', 'Akun Telegram diputus. Kamu hanya akan melihat notifikasi di web.');
    }
}
