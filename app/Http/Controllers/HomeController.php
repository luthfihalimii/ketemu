<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Models\Item;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'availableCount' => Item::query()->where('status', ItemStatus::Stored->value)->count(),
            'returnedCount' => Item::query()->where('status', ItemStatus::Returned->value)->count(),
            'latestItems' => Item::query()
                ->discoverable()
                ->with(['category:id,name', 'location:id,name'])
                ->latest('occurred_at')
                ->take(6)
                ->get(),
        ]);
    }
}
