<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Http\Requests\Items\StoreFoundItemRequest;
use App\Http\Requests\Items\StoreLostItemRequest;
use App\Http\Requests\Items\UpdateItemRequest;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Services\ItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function __construct(private readonly ItemService $items) {}

    /**
     * Public Lost & Found catalogue with simple filters.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'location' => ['nullable', 'integer', 'exists:locations,id'],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
        ]);

        $items = Item::query()
            ->discoverable()
            ->with(['category:id,name,icon', 'location:id,name'])
            ->when(filled($filters['q'] ?? null), function ($query) use ($filters) {
                $raw = trim((string) $filters['q']);
                // MySQL: FULLTEXT jauh lebih cepat + relevan untuk katalog besar.
                // SQLite (test) tidak punya MATCH, jadi fallback LIKE.
                if (DB::connection()->getDriverName() === 'mysql' && mb_strlen($raw) >= 3) {
                    $query->whereRaw('MATCH(title, description, color, brand) AGAINST(? IN NATURAL LANGUAGE MODE)', [$raw]);
                } else {
                    $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $raw).'%';
                    $query->where(function ($query) use ($term) {
                        $query->whereRaw("title LIKE ? ESCAPE '!'", [$term])
                            ->orWhereRaw("description LIKE ? ESCAPE '!'", [$term])
                            ->orWhereRaw("color LIKE ? ESCAPE '!'", [$term])
                            ->orWhereRaw("brand LIKE ? ESCAPE '!'", [$term]);
                    });
                }
            })
            ->when(filled($filters['category'] ?? null), fn ($query) => $query->where('category_id', $filters['category']))
            ->when(filled($filters['location'] ?? null), fn ($query) => $query->where('location_id', $filters['location']))
            ->when(filled($filters['date'] ?? null), fn ($query) => $query->whereDate('occurred_at', $filters['date']))
            ->when(filled($filters['status'] ?? null), function ($query) use ($filters) {
                if (ItemStatus::tryFrom($filters['status'])?->isPubliclyAvailable()) {
                    $query->where('status', $filters['status']);
                }
            })
            ->latest('occurred_at')
            ->paginate(12)
            ->withQueryString();

        return view('items.index', [
            'items' => $items,
            'categories' => Category::query()->active()->orderBy('sort_order')->get(),
            'locations' => Location::query()->active()->orderBy('name')->get(),
            'filters' => $filters,
            'statusOptions' => collect(ItemStatus::cases())
                ->filter(fn (ItemStatus $status) => $status->isPubliclyAvailable())
                ->mapWithKeys(fn (ItemStatus $status) => [$status->value => $status->label()]),
        ]);
    }

    /**
     * Public item detail. The verification answer and private_note are never
     * part of the public payload; private_note only goes to owner/guard/admin
     * or an approved claimant.
     */
    public function show(Item $item): View
    {
        Gate::authorize('view', $item);

        $item->load(['category:id,name,icon', 'location:id,name', 'depositLocation:id,name', 'user:id,name']);

        $user = auth()->user();
        $myClaim = $user
            ? $item->claims()->where('user_id', $user->id)->first()
            : null;

        $canSeePrivate = $user !== null && (
            $user->id === $item->user_id
            || $user->isAdmin()
            || $user->canVerifyPickup()
            || ($myClaim !== null && $myClaim->isApproved())
        );

        $timeline = AuditLog::query()
            ->where('auditable_type', (new Item)->getMorphClass())
            ->where('auditable_id', $item->id)
            ->with('user:id,name')
            ->latest('created_at')
            ->limit(20)
            ->get();

        return view('items.show', [
            'item' => $item,
            'myClaim' => $myClaim,
            'canClaim' => $user !== null && Gate::allows('claim', $item),
            'canSeePrivate' => $canSeePrivate,
            'timeline' => $timeline,
        ]);
    }

    public function createFound(): View
    {
        return view('items.create-found', [
            'categories' => Category::query()->active()->orderBy('sort_order')->get(),
            'locations' => Location::query()->active()->orderBy('name')->get(),
            'depositLocations' => Location::query()->securityPosts()->orderBy('name')->get(),
        ]);
    }

    public function storeFound(StoreFoundItemRequest $request): RedirectResponse
    {
        $item = $this->items->createFound(
            $request->user(),
            Arr::except($request->validated(), ['photo']),
            $request->file('photo'),
        );

        $message = $request->boolean('confirm_deposit')
            ? 'Laporan tersimpan dan barang sudah tercatat dititipkan ke satpam.'
            : 'Laporan berhasil dikirim. Jangan lupa menitipkan barang ke satpam agar dapat diklaim.';

        return redirect()->route('items.show', $item)->with('status', $message);
    }

    public function createLost(): View
    {
        return view('items.create-lost', [
            'categories' => Category::query()->active()->orderBy('sort_order')->get(),
            'locations' => Location::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function storeLost(StoreLostItemRequest $request): RedirectResponse
    {
        $item = $this->items->createLost(
            $request->user(),
            Arr::except($request->validated(), ['photo']),
            $request->file('photo'),
        );

        return redirect()->route('items.show', $item)
            ->with('status', 'Laporan barang hilang berhasil dibuat.');
    }

    /**
     * Finder confirms the item is physically held by security staff.
     *
     * Ini atestasi penemu (langkah 1 dari 2). Satpam wajib memverifikasi
     * fisik via guard.deposit.confirm sebelum badge terkonfirmasi muncul.
     */
    public function confirmDeposit(Item $item): RedirectResponse
    {
        Gate::authorize('confirmDeposit', $item);

        if (! $item->status->canTransitionTo(ItemStatus::Stored)) {
            return back()->withErrors([
                'deposit' => 'Barang ini sudah tidak dapat dikonfirmasi penitipannya.',
            ]);
        }

        $this->items->confirmDeposit($item);

        return back()->with('status', 'Permintaan penitipan tercatat. Minta satpam memverifikasi barang fisik di pos agar status terkonfirmasi.');
    }

    public function edit(Item $item): View
    {
        Gate::authorize('update', $item);

        if (! in_array($item->status, [ItemStatus::WaitingDeposit, ItemStatus::Reported], true)) {
            abort(403, 'Laporan sudah tidak dapat diubah. Hubungi admin bila ada kesalahan data.');
        }

        return view('items.edit', [
            'item' => $item->load(['category:id,name', 'location:id,name', 'depositLocation:id,name']),
            'categories' => Category::query()->active()->orderBy('sort_order')->get(),
            'locations' => Location::query()->active()->orderBy('name')->get(),
            'depositLocations' => Location::query()->securityPosts()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        Gate::authorize('update', $item);

        $updated = $this->items->updateReport(
            $item,
            Arr::except($request->validated(), ['photo']),
            $request->file('photo'),
        );

        return redirect()->route('items.show', $updated)->with('status', 'Laporan berhasil diperbarui.');
    }
}
