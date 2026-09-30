<?php

namespace Tests\Feature\Claims;

use App\Enums\ItemStatus;
use App\Enums\PickupCodeStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

/**
 * A pickup code is a bearer token: whoever holds it can collect the goods.
 * It must be single-use, and it must never release an item that is not
 * actually waiting for collection.
 */
class PickupCodeRedeemGuardTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function a_code_can_only_be_redeemed_once(): void
    {
        $item = $this->storedItem();
        $code = $this->verifyClaim($item, $this->student());
        $guard = User::factory()->guard()->create();

        $this->redeem($code, $guard)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(ItemStatus::Returned, $item->refresh()->status);

        // Replaying the same code must fail and must not disturb the item.
        $this->redeem($code, $guard)->assertSessionHasErrors('code');

        $this->assertSame(ItemStatus::Returned, $item->refresh()->status);
    }

    #[Test]
    public function a_stale_code_cannot_release_an_item_that_left_the_pickup_stage(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $code = $this->verifyClaim($item, $claimant);

        // Item moved back to the shelf while the old code was still unexpired.
        $item->refresh();
        $item->setStatus(ItemStatus::Stored);
        $item->save();

        $this->redeem($code, User::factory()->guard()->create())
            ->assertSessionHasErrors('code');

        $this->assertSame(ItemStatus::Stored, $item->refresh()->status);

        // The transaction rolled back: the code was not burned and the claim
        // is still open, so an admin reissue path stays coherent.
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->assertSame(PickupCodeStatus::Active, $claim->pickupCode->refresh()->status);
        $this->assertNull($claim->pickupCode->used_at);
    }

    #[Test]
    public function verified_items_can_be_handed_over_directly(): void
    {
        // Regression: this transition used to require VERIFIED -> READY_FOR_PICKUP
        // -> RETURNED, a two-step hop that existed only to satisfy the enum.
        $this->assertTrue(ItemStatus::Verified->canTransitionTo(ItemStatus::Returned));
        $this->assertFalse(ItemStatus::ReadyForPickup->canTransitionTo(ItemStatus::Expired));
    }

    #[Test]
    public function a_deposit_confirmation_cannot_skip_straight_to_returned(): void
    {
        $item = new Item(['status' => ItemStatus::WaitingDeposit]);

        $this->expectException(InvalidStatusTransitionException::class);

        $item->setStatus(ItemStatus::Returned);
    }
}
