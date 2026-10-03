<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * The signed-in user's notification inbox.
     */
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()
                ->notifications()
                ->latest()
                ->paginate(20),
        ]);
    }

    /**
     * Follow a notification to the thing it is about, marking it read.
     */
    public function show(Request $request, string $notification): RedirectResponse
    {
        $record = $this->findFor($request, $notification);

        $record->markAsRead();

        $url = $record->data['url'] ?? null;

        if (! $this->isSafeUrl($url)) {
            return redirect()->route('dashboard');
        }

        return redirect($url);
    }

    /**
     * Open-redirect guard: only allow relative paths (not protocol-relative
     * "//host") or absolute URLs on the application's own host. Anything else
     * falls back to the dashboard.
     */
    private function isSafeUrl(mixed $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        if (str_starts_with($url, '//')) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host)
            && $host !== ''
            && $host === parse_url((string) config('app.url'), PHP_URL_HOST);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /**
     * Resolve a notification belonging to the current user, or abort.
     *
     * Notifications are looked up through the user's own relation, so one
     * student can never open another student's notification by guessing an id.
     */
    private function findFor(Request $request, string $id): DatabaseNotification
    {
        return $request->user()->notifications()->whereKey($id)->firstOrFail();
    }
}
