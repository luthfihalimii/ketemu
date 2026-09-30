<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\ModerateItemRequest;
use App\Models\Claim;
use App\Services\ModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClaimModerationController extends Controller
{
    public function __construct(private readonly ModerationService $moderation) {}

    /**
     * Claims waiting on administrative review.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string'],
        ]);

        $status = filled($filters['status'] ?? null) ? ClaimStatus::tryFrom($filters['status']) : null;

        $claims = Claim::query()
            ->with(['item:id,title,code,status', 'user:id,name,email', 'pickupCode'])
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.claims.index', [
            'claims' => $claims,
            'filters' => $filters,
            'statusOptions' => collect(ClaimStatus::cases())
                ->mapWithKeys(fn (ClaimStatus $case) => [$case->value => $case->label()]),
        ]);
    }

    public function reject(ModerateItemRequest $request, Claim $claim): RedirectResponse
    {
        try {
            $this->moderation->rejectClaim($claim, $request->validated('reason'), $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Klaim ditolak dan barang kembali tersedia.');
    }

    public function reissueCode(Claim $claim): RedirectResponse
    {
        Gate::authorize('moderate', $claim->item);

        try {
            $result = $this->moderation->reissueCode($claim, request()->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()
            ->with('status', 'Kode pengambilan baru diterbitkan.')
            ->with('reissued_code', $result['plain']);
    }
}
