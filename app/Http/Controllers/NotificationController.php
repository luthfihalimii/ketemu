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

        return redirect($record->data['url'] ?? route('dashboard'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

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
