<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:60', 'unique:categories,name'],
            'icon' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'icon' => $validated['icon'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        $this->audit->log(event: 'moderation.category_created', description: "Kategori {$category->name} dibuat.", auditable: $category);

        return back()->with('status', "Kategori {$category->name} dibuat.");
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:60', Rule::unique('categories', 'name')->ignore($category->id)],
            'icon' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $previous = $category->only(['name', 'icon', 'sort_order']);
        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'icon' => $validated['icon'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $this->audit->log(
            event: 'moderation.category_updated',
            description: "Kategori {$category->name} diperbarui.",
            auditable: $category,
            properties: ['previous_value' => $previous, 'new_value' => $category->only(['name', 'icon', 'sort_order'])],
        );

        return back()->with('status', "Kategori {$category->name} diperbarui.");
    }
}
