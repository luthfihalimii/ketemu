<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\ModerateItemRequest;
use App\Models\Item;
use App\Services\ModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ItemModerationController extends Controller
{
    public function __construct(private readonly ModerationService $moderation) {}

    /**
     * Moderation queue: flagged reports first, then the newest submissions.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string'],
            'only' => ['nullable', 'in:flagged,deposit_overdue'],
        ]);

        $items = Item::query()
            ->with(['category:id,name', 'user:id,name,email', 'depositLocation:id,name'])
            ->when(($filters['only'] ?? null) === 'flagged', fn ($query) => $query->flagged())
            ->when(($filters['only'] ?? null) === 'deposit_overdue', fn ($query) => $query->depositOverdue())
            ->when(
                filled($filters['status'] ?? null) && ItemStatus::tryFrom($filters['status']) !== null,
                fn ($query) => $query->where('status', $filters['status']),
            )
            ->orderByRaw('flagged_at IS NULL')
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.items.index', [
            'items' => $items,
            'filters' => $filters,
            'flagCount' => Item::query()->flagged()->count(),
            'depositFollowUpCount' => Item::query()->depositOverdue()->count(),
            'statusOptions' => ItemStatus::options(),
        ]);
    }

    public function show(Item $item): View
    {
        $item->load([
            'category:id,name',
            'location:id,name',
            'depositLocation:id,name',
            'user:id,name,email,role',
            'moderator:id,name',
            'claims' => fn ($query) => $query->latest(),
            'claims.user:id,name,email',
            'claims.pickupCode',
        ]);

        return view('admin.items.show', [
            'item' => $item,
            'canReject' => $item->status->canTransitionTo(ItemStatus::Rejected),
            'canRestore' => $item->status === ItemStatus::Rejected,
            'canExpire' => $item->status->canTransitionTo(ItemStatus::Expired),
        ]);
    }

    public function flag(ModerateItemRequest $request, Item $item): RedirectResponse
    {
        $this->moderation->flag($item, $request->validated('reason'), $request->user());

        return back()->with('status', 'Laporan ditandai mencurigakan.');
    }

    public function unflag(Item $item): RedirectResponse
    {
        $this->moderation->unflag($item, request()->user());

        return back()->with('status', 'Tanda mencurigakan pada laporan dihapus.');
    }

    public function reject(ModerateItemRequest $request, Item $item): RedirectResponse
    {
        try {
            $this->moderation->reject($item, $request->validated('reason'), $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Laporan dinonaktifkan dan seluruh klaim aktif dibatalkan.');
    }

    public function restore(Item $item): RedirectResponse
    {
        try {
            $this->moderation->restore($item, request()->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Laporan dipulihkan.');
    }

    public function expire(Item $item): RedirectResponse
    {
        try {
            $this->moderation->expire($item, request()->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Laporan dikedaluwarsakan dan hilang dari pencarian publik.');
    }
}
