<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Accounts overview. Only admins reach this route, and no personal data
     * is exposed beyond what an administrator needs to act on.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $users = User::query()
            ->when(filled($filters['q'] ?? null), function ($query) use ($filters) {
                $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['q']).'%';
                $query->where(fn ($query) => $query->whereRaw("name LIKE ? ESCAPE '!'", [$term])->orWhereRaw("email LIKE ? ESCAPE '!'", [$term]));
            })
            ->withCount(['items', 'claims'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users]);
    }

    public function ban(User $user): RedirectResponse
    {
        if ($user->id === request()->user()->id) {
            return back()->withErrors(['user' => 'Kamu tidak dapat menonaktifkan akun sendiri.']);
        }

        $user->forceFill(['banned_at' => now()])->save();

        $this->audit->log(
            event: 'admin.user_banned',
            description: "Akun {$user->email} dinonaktifkan.",
            auditable: $user,
            properties: ['email' => $user->email],
        );

        return back()->with('status', "Akun {$user->email} dinonaktifkan.");
    }

    public function unban(User $user): RedirectResponse
    {
        $user->forceFill(['banned_at' => null])->save();

        $this->audit->log(
            event: 'admin.user_unbanned',
            description: "Akun {$user->email} dipulihkan.",
            auditable: $user,
            properties: ['email' => $user->email],
        );

        return back()->with('status', "Akun {$user->email} dipulihkan.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        if ($user->id === $request->user()->id && Role::from($validated['role']) !== Role::Admin) {
            return back()->withErrors(['role' => 'Kamu tidak dapat menurunkan peran sendiri.']);
        }

        $previous = $user->role;
        $user->forceFill(['role' => $validated['role']])->save();

        $this->audit->log(
            event: 'admin.user_role_changed',
            description: "Peran {$user->email} diubah.",
            auditable: $user,
            properties: ['previous_value' => $previous->value, 'new_value' => $validated['role']],
        );

        return back()->with('status', "Peran {$user->email} diubah menjadi {$validated['role']}.");
    }
}
