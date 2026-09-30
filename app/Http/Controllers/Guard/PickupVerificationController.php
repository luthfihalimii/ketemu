<?php

namespace App\Http\Controllers\Guard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Claims\RedeemPickupCodeRequest;
use App\Models\Item;
use App\Services\PickupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PickupVerificationController extends Controller
{
    public function __construct(private readonly PickupService $pickups) {}

    /**
     * The simple digital step security staff need: enter the code.
     */
    public function create(): View
    {
        return view('guard.verify', [
            'lastPickup' => session('last_pickup_item_id')
                ? Item::query()->find(session('last_pickup_item_id'))
                : null,
            'lastRecipient' => session('last_pickup_recipient'),
        ]);
    }

    public function store(RedeemPickupCodeRequest $request): RedirectResponse
    {
        $recipientName = $request->validated('recipient_name');

        $pickupCode = $this->pickups->redeem(
            $request->validated('code'),
            $request->user(),
            $request->validated('recipient_id_number'),
            $recipientName,
        );

        $pickupCode->loadMissing('item');

        return redirect()
            ->route('guard.pickup.create')
            ->with('status', 'Kode valid. Barang "'.$pickupCode->item->title.'" telah diserahkan kepada '.$recipientName.'.')
            ->with('last_pickup_item_id', $pickupCode->item_id)
            ->with('last_pickup_recipient', $recipientName);
    }
}
