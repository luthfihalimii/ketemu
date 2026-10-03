<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimStatus;
use App\Enums\ItemStatus;
use App\Enums\PickupCodeStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Claim;
use App\Models\Item;
use App\Models\PickupCode;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $itemsByStatus = Item::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $claimsByStatus = Claim::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return view('admin.dashboard', [
            'stats' => [
                'items_total' => Item::query()->count(),
                'items_stored' => Item::query()->where('status', ItemStatus::Stored->value)->count(),
                'items_waiting_deposit' => Item::query()->where('status', ItemStatus::WaitingDeposit->value)->count(),
                'items_returned' => Item::query()->where('status', ItemStatus::Returned->value)->count(),
                'items_flagged' => Item::query()->flagged()->count(),
                'deposit_overdue' => Item::query()->depositOverdue()->count(),
                'unconfirmed_deposits' => Item::query()->whereNotNull('deposit_requested_at')->whereNull('deposit_confirmed_at')->count(),
                'claims_active' => Claim::query()->active()->count(),
                'claims_completed' => Claim::query()->where('status', ClaimStatus::Completed->value)->count(),
                'pickup_active' => PickupCode::query()->where('status', PickupCodeStatus::Active->value)->count(),
                'users_total' => User::query()->count(),
                'users_banned' => User::query()->whereNotNull('banned_at')->count(),
            ],
            'itemsByStatus' => $itemsByStatus,
            'claimsByStatus' => $claimsByStatus,
            'recentLogs' => AuditLog::query()->with('user:id,name')->latest('created_at')->limit(10)->get(),
        ]);
    }
}
