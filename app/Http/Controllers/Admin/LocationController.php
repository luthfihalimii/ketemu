<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.locations.index', [
            'locations' => Location::query()->withCount(['items', 'deposits'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120', 'unique:locations,name'],
            'type' => ['required', Rule::in(['campus', 'security_post'])],
            'detail' => ['nullable', 'string', 'max:255'],
        ]);

        $location = Location::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'detail' => $validated['detail'] ?? null,
            'is_active' => true,
        ]);

        $this->audit->log(event: 'moderation.location_created', description: "Lokasi {$location->name} dibuat.", auditable: $location);

        return back()->with('status', "Lokasi {$location->name} dibuat.");
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120', Rule::unique('locations', 'name')->ignore($location->id)],
            'type' => ['required', Rule::in(['campus', 'security_post'])],
            'detail' => ['nullable', 'string', 'max:255'],
        ]);

        $previous = $location->only(['name', 'type', 'detail']);
        $location->update($validated);

        $this->audit->log(
            event: 'moderation.location_updated',
            description: "Lokasi {$location->name} diperbarui.",
            auditable: $location,
            properties: ['previous_value' => $previous, 'new_value' => $location->only(['name', 'type', 'detail'])],
        );

        return back()->with('status', "Lokasi {$location->name} diperbarui.");
    }

    public function toggle(Location $location): RedirectResponse
    {
        $previous = $location->is_active;
        $location->update(['is_active' => ! $previous]);

        $this->audit->log(
            event: 'moderation.location_toggled',
            description: "Status lokasi {$location->name} diubah.",
            auditable: $location,
            properties: ['previous_value' => $previous, 'new_value' => $location->is_active],
        );

        return back()->with('status', 'Status lokasi diperbarui.');
    }
}
