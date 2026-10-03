<?php

namespace App\Http\Controllers\Guard;

use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Claims\RedeemPickupCodeRequest;
use App\Models\Item;
use App\Models\PickupCode;
use App\Services\ItemService;
use App\Services\PickupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PickupVerificationController extends Controller
{
    public function __construct(private readonly PickupService $pickups, private readonly ItemService $items) {}

    /**
     * The simple digital step security staff need: enter the code.
     */
    public function create(): View
    {
        $pendingDeposits = Item::query()
            ->whereNotNull('deposit_requested_at')
            ->whereNull('deposit_confirmed_at')
            ->whereIn('status', [ItemStatus::WaitingDeposit->value, ItemStatus::Stored->value])
            ->with(['category:id,name', 'depositLocation:id,name'])
            ->latest('deposit_requested_at')
            ->limit(20)
            ->get();

        return view('guard.verify', [
            'lastPickup' => session('last_pickup_item_id')
                ? Item::query()->find(session('last_pickup_item_id'))
                : null,
            'lastRecipient' => session('last_pickup_recipient'),
            'pendingDeposits' => $pendingDeposits,
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

    /**
     * Langkah 2 penitipan 2-pihak: satpam memastikan fisik barang ada di pos.
     */
    public function confirmDeposit(Item $item): RedirectResponse
    {
        $this->items->confirmDepositByGuard($item, request()->user());

        return back()->with('status', 'Penitipan "'.$item->title.'" terkonfirmasi. Barang kini berstatus terverifikasi fisik satpam.');
    }

    /**
     * Struk serah terima untuk arsip pos: hanya ID tersamar, bukan PII utuh.
     */
    public function receipt(PickupCode $pickupCode): View
    {
        $pickupCode->load(['item.category:id,name', 'item.depositLocation:id,name', 'user:id,name', 'verifier:id,name']);

        return view('guard.receipt', ['pickupCode' => $pickupCode, 'item' => $pickupCode->item]);
    }
}
