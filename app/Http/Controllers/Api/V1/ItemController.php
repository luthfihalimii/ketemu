<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'location' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        $items = Item::query()
            ->discoverable()
            ->with(['category:id,name', 'location:id,name', 'depositLocation:id,name'])
            ->when(filled($filters['q'] ?? null), function ($query) use ($filters) {
                $raw = trim((string) $filters['q']);
                if (DB::connection()->getDriverName() === 'mysql' && mb_strlen($raw) >= 3) {
                    $query->whereRaw('MATCH(title, description, color, brand) AGAINST(? IN NATURAL LANGUAGE MODE)', [$raw]);
                } else {
                    $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $raw).'%';
                    $query->where(fn ($q) => $q->whereRaw("title LIKE ? ESCAPE '!'", [$term])->orWhereRaw("description LIKE ? ESCAPE '!'", [$term]));
                }
            })
            ->when(filled($filters['category'] ?? null), fn ($q) => $q->where('category_id', $filters['category']))
            ->when(filled($filters['location'] ?? null), fn ($q) => $q->where('location_id', $filters['location']))
            ->latest('occurred_at')
            ->paginate(12);

        return ItemResource::collection($items);
    }

    public function show(Item $item)
    {
        Gate::authorize('view', $item);
        $item->load(['category:id,name', 'location:id,name', 'depositLocation:id,name']);

        return new ItemResource($item);
    }
}
