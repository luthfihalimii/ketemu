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
    public function a_wrong_pickup_code_leaves_an_audit_record_after_validation_fails(): void
    {
        $guard = User::factory()->guard()->create();

        $this->redeem('XXXX-XXXX', $guard)->assertSessionHasErrors('code');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'pickup.failed',
            'user_id' => $guard->id,
        ]);
        $this->assertDatabaseCount('pickup_codes', 0);
    }

    #[Test]
    public function a_non_numeric_recipient_id_number_is_rejected(): void
    {
        $item = $this->storedItem();
        $code = $this->verifyClaim($item, $this->student());
        $guard = User::factory()->guard()->create();

        $this->redeem($code, $guard, 'KTP-2141720', 'Budi Santoso')
            ->assertSessionHasErrors('recipient_id_number');
    }

    #[Test]
    public function a_guard_is_locked_out_after_too_many_wrong_codes(): void
    {
        $guard = User::factory()->guard()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->redeem('KODE-SALAH'.$i, $guard)->assertSessionHasErrors('code');
        }

        // Percobaan ke-6 diblokir oleh lockout, bukan sekadar "kode salah".
        $response = $this->redeem('KODE-SALAH-LAGI', $guard);

        $response->assertSessionHasErrors('code');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan kode salah',
            (string) session('errors')->first('code'),
        );
    }

    #[Test]
    public function a_successful_redeem_clears_the_wrong_code_counter(): void
    {
        $guard = User::factory()->guard()->create();

        $this->redeem('SALAH-1', $guard)->assertSessionHasErrors('code');
        $this->redeem('SALAH-2', $guard)->assertSessionHasErrors('code');

        $item = $this->storedItem();
        $code = $this->verifyClaim($item, $this->student());

        $this->redeem($code, $guard)->assertSessionHasNoErrors();

        // Penghitung sudah direset; salah tebus berikutnya mulai dari awal.
        $this->redeem('SALAH-3', $guard)->assertSessionHasErrors('code');
        $this->assertStringNotContainsString(
            'Terlalu banyak',
            (string) session('errors')->first('code'),
        );
    }

    #[Test]
    public function a_code_can_only_be_redeemed_once(): void
    {
        $item = $this->storedItem();
        $code = $this->verifyClaim($item, $this->student());
        $guard = User::factory()->guard()->create();

        $this->redeem($code, $guard)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertEquals(ItemStatus::Returned, $item->refresh()->status);

        // Replaying the same code must fail and must not disturb the item.
        $this->redeem($code, $guard)->assertSessionHasErrors('code');

        $this->assertEquals(ItemStatus::Returned, $item->refresh()->status);
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
        $item = new Item;
        $item->status = ItemStatus::WaitingDeposit;

        $this->expectException(InvalidStatusTransitionException::class);

        $item->setStatus(ItemStatus::Returned);
    }
}
