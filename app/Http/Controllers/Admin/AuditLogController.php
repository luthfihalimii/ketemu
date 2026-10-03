<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Audit trail of important activities.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'event' => ['nullable', 'string', 'max:100'],
            'user' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when(filled($filters['event'] ?? null), fn ($query) => $query->whereRaw("event LIKE ? ESCAPE '!'", [str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['event']).'%']))
            ->when(filled($filters['user'] ?? null), fn ($query) => $query->where('user_id', $filters['user']))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit.index', [
            'logs' => $logs,
            'filters' => $filters,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
