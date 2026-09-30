<?php

namespace App\Http\Controllers;

use App\Enums\ClaimStatus;
use App\Http\Requests\Claims\SubmitClaimRequest;
use App\Models\Claim;
use App\Models\Item;
use App\Services\ClaimVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClaimController extends Controller
{
    public function __construct(private readonly ClaimVerificationService $claims) {}

    /**
     * Verification form. The question is shown; the answer never is.
     */
    public function create(Item $item): View|RedirectResponse
    {
        $user = request()->user();

        $existing = $item->claims()->where('user_id', $user->id)->first();

        if ($existing !== null && $existing->status === ClaimStatus::Approved) {
            return redirect()->route('claims.pickup', $existing);
        }

        Gate::authorize('claim', $item);

        return view('claims.create', [
            'item' => $item->load(['category:id,name', 'location:id,name', 'depositLocation:id,name']),
            'claim' => $existing,
            'remainingAttempts' => ClaimVerificationService::MAX_ATTEMPTS - ($existing?->attempt_count ?? 0),
            'maxAttempts' => ClaimVerificationService::MAX_ATTEMPTS,
        ]);
    }

    /**
     * Validate the ownership answer and, when correct, issue a pickup code.
     */
    public function store(SubmitClaimRequest $request, Item $item): RedirectResponse
    {
        Gate::authorize('claim', $item);

        $result = $this->claims->submit($item, $request->user(), $request->validated('answer'));

        if ($result['plain_code'] !== null) {
            return redirect()
                ->route('claims.pickup', $result['claim'])
                ->with('status', 'Verifikasi berhasil! Tunjukkan kode pengambilan kepada petugas keamanan.');
        }

        $claim = $result['claim'];

        if ($claim->status === ClaimStatus::Rejected) {
            return redirect()
                ->route('items.show', $item)
                ->withErrors([
                    'answer' => 'Verifikasi gagal. Batas percobaan sudah habis, silakan hubungi admin bila barang benar-benar milikmu.',
                ]);
        }

        $remaining = ClaimVerificationService::MAX_ATTEMPTS - $claim->attempt_count;

        return back()->withErrors([
            'answer' => "Jawaban belum cocok. Sisa percobaan: {$remaining}.",
        ]);
    }

    /**
     * Owner's pickup page with the single-use code.
     */
    public function pickup(Claim $claim): View
    {
        // Load before authorizing so the policy can read the related item.
        $claim->load(['item.category:id,name', 'item.location:id,name', 'item.depositLocation:id,name', 'pickupCode']);

        Gate::authorize('view', $claim);

        return view('claims.pickup', [
            'claim' => $claim,
            'item' => $claim->item,
            'pickupCode' => $claim->pickupCode,
        ]);
    }
}
