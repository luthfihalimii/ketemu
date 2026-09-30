<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::query()
                ->withCount('items')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function toggle(Category $category): RedirectResponse
    {
        $previous = $category->is_active;

        $category->update(['is_active' => ! $previous]);

        $this->audit->log(
            event: 'moderation.category_toggled',
            description: 'Status kategori diubah oleh admin.',
            auditable: $category,
            properties: [
                'previous_value' => $previous,
                'new_value' => $category->is_active,
            ],
        );

        return back()->with('status', 'Status kategori diperbarui.');
    }
}
