<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Exceptions\InvalidMatchException;
use App\Models\Claim;
use App\Models\Item;
use App\Services\ClaimVerificationService;
use App\Services\ItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ClaimVerificationService $claims,
        private readonly ItemService $items,
    ) {}

    public function index(): View
    {
        $user = request()->user();

        $myReports = Item::query()
            ->where('user_id', $user->id)
            ->with('category:id,name')
            ->latest()
            ->limit(5)
            ->get();

        $myClaims = $user->claims()
            ->with(['item.category:id,name', 'item.location:id,name', 'pickupCode'])
            ->latest()
            ->limit(5)
            ->get();

        // Aggregasi statistik dihitung di database agar dasbor tidak memuat
        // seluruh riwayat laporan/klaim hanya untuk menghitung angka.
        /** @var object{reports: int|string|null, awaiting_deposit: int|string|null, available: int|string|null} $reportStats */
        $reportStats = Item::query()
            ->where('user_id', $user->id)
            ->selectRaw('count(*) as reports')
            ->selectRaw('sum(status = ?) as awaiting_deposit', [ItemStatus::WaitingDeposit->value])
            ->selectRaw('sum(status = ?) as available', [ItemStatus::Stored->value])
            ->toBase()
            ->firstOrFail();

        $claimCount = $user->claims()->count();
        $readyForPickup = $user->claims()
            ->whereHas('item', fn ($query) => $query->where('status', ItemStatus::ReadyForPickup->value))
            ->count();

        return view('dashboard.index', [
            'user' => $user,
            'stats' => [
                'reports' => (int) $reportStats->reports,
                'awaiting_deposit' => (int) $reportStats->awaiting_deposit,
                'available' => (int) $reportStats->available,
                'claims' => $claimCount,
                'ready_for_pickup' => $readyForPickup,
            ],
            'myReports' => $myReports,
            'myClaims' => $myClaims,
        ]);
    }

    /**
     * Reports submitted by the signed-in student.
     */
    public function reports(): View
    {
        $items = Item::query()
            ->where('user_id', request()->user()->id)
            ->with(['category:id,name', 'depositLocation:id,name', 'matchedItem:id,title,code,status'])
            ->latest()
            ->paginate(10);

        return view('dashboard.reports', ['items' => $items]);
    }

    /**
     * Suggested found items for one of the student's lost reports.
     *
     * Read-only: a suggestion is only a shortcut into the normal claim flow,
     * which still requires the finder's verification answer.
     */
    public function matches(Item $item): View
    {
        Gate::authorize('view', $item);

        abort_unless($item->isLostReport(), 404);

        return view('dashboard.matches', [
            'item' => $item->load(['category:id,name', 'location:id,name']),
            'candidates' => $this->items->candidateFoundItems($item),
        ]);
    }

    /**
     * Record that a lost report and a found item describe the same belonging.
     */
    public function storeMatch(Request $request, Item $item): RedirectResponse
    {
        Gate::authorize('update', $item);

        $validated = $request->validate([
            'found_item_id' => ['required', 'integer', 'exists:items,id'],
        ]);

        $found = Item::query()->findOrFail($validated['found_item_id']);
        Gate::authorize('view', $found);

        try {
            $this->items->matchLostToFound($item, $found, $request->user());
        } catch (InvalidMatchException $exception) {
            return back()->withErrors(['found_item_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('dashboard.reports')
            ->with('status', 'Laporan dicocokkan. Ajukan klaim pada barang temuan tersebut untuk membuktikan kepemilikan.');
    }

    public function destroyMatch(Item $item): RedirectResponse
    {
        Gate::authorize('update', $item);

        try {
            $this->items->unmatchLost($item, request()->user());
        } catch (InvalidMatchException $exception) {
            return back()->withErrors(['match' => $exception->getMessage()]);
        }

        return back()->with('status', 'Kecocokan dibatalkan.');
    }

    /**
     * Claims opened by the signed-in student.
     */
    public function claims(): View
    {
        $claims = request()->user()->claims()
            ->with(['item.category:id,name', 'item.location:id,name', 'item.depositLocation:id,name', 'pickupCode'])
            ->latest()
            ->paginate(10);

        return view('dashboard.claims', [
            'claims' => $claims,
            'maxAttempts' => ClaimVerificationService::MAX_ATTEMPTS,
        ]);
    }

    /**
     * Cancel an open claim and return the item to the shelf.
     */
    public function cancelClaim(Claim $claim): RedirectResponse
    {
        Gate::authorize('cancel', $claim);

        $this->claims->release($claim);

        return back()->with('status', 'Klaim dibatalkan. Kamu dapat mencoba lagi bila masih membutuhkan barang ini.');
    }
}
