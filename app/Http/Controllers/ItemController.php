<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Http\Requests\Items\StoreFoundItemRequest;
use App\Http\Requests\Items\StoreLostItemRequest;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Services\ItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
                $term = '%'.$filters['q'].'%';
                $query->where(function ($query) use ($term) {
                    $query->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('color', 'like', $term)
                        ->orWhere('brand', 'like', $term);
                });
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
     * Public item detail. The verification answer is never part of the payload.
     */
    public function show(Item $item): View
    {
        Gate::authorize('view', $item);

        $item->load(['category:id,name,icon', 'location:id,name', 'depositLocation:id,name', 'user:id,name']);

        $myClaim = auth()->check()
            ? $item->claims()->where('user_id', auth()->id())->first()
            : null;

        return view('items.show', [
            'item' => $item,
            'myClaim' => $myClaim,
            'canClaim' => auth()->check() && Gate::allows('claim', $item),
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
            Arr::except($request->validated(), ['photo', 'confirm_deposit']),
            $request->file('photo'),
        );

        if ($request->boolean('confirm_deposit')) {
            $this->items->confirmDeposit($item);
        }

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

        return back()->with('status', 'Barang kini tersedia dan dapat diklaim pemiliknya.');
    }
}
