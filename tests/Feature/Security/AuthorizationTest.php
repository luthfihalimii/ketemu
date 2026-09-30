<?php

namespace Tests\Feature\Security;

use App\Enums\ItemStatus;
use App\Models\Claim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function a_student_cannot_read_another_students_claim_or_pickup_code(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();
        $attacker = $this->student();

        $this->verifyClaim($item, $claimant);
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->actingAs($attacker)
            ->get(route('claims.pickup', $claim))
            ->assertForbidden();
    }

    #[Test]
    public function the_reporter_can_see_the_claim_on_their_item_but_not_its_secrets(): void
    {
        $reporter = $this->student();
        $item = $this->storedItem([], $reporter);
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->actingAs($reporter)
            ->get(route('claims.pickup', $claim))
            ->assertOk()
            ->assertDontSee($claimant->email);
    }

    #[Test]
    public function the_reporter_cannot_cancel_another_students_claim(): void
    {
        $reporter = $this->student();
        $item = $this->storedItem([], $reporter);

        $this->verifyClaim($item, $this->student());
        $claim = $item->claims()->firstOrFail();

        $this->actingAs($reporter)
            ->delete(route('claims.cancel', $claim))
            ->assertForbidden();
    }

    #[Test]
    public function a_student_cannot_confirm_a_deposit_on_an_item_they_did_not_report(): void
    {
        $item = $this->storedItem(['status' => ItemStatus::Reported, 'stored_at' => null]);

        $this->actingAs($this->student())
            ->post(route('items.confirm-deposit', $item))
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_can_view_a_pending_item_and_its_claim(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem(['status' => ItemStatus::Reported]);
        $claim = Claim::factory()->create(['item_id' => $item->id]);

        $this->actingAs($admin)->get(route('items.show', $item))->assertOk();
        $this->actingAs($admin)->get(route('claims.pickup', $claim))->assertOk();
    }

    #[Test]
    public function the_pickup_panel_never_leaks_codes_through_the_dashboard_of_a_student(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();
        $plain = $this->verifyClaim($item, $claimant);

        // Another student's dashboard must not contain the code.
        $this->actingAs($this->student())
            ->get(route('dashboard.claims'))
            ->assertOk()
            ->assertDontSee($plain, escape: false);
    }

    #[Test]
    public function an_item_with_a_pending_claim_stays_hidden_from_guests_when_not_deposited(): void
    {
        $item = $this->storedItem(['status' => ItemStatus::WaitingDeposit]);

        $this->get(route('items.index'))->assertOk()->assertDontSee($item->title);
        $this->get(route('items.show', $item))->assertForbidden();
    }

    #[Test]
    public function an_unknown_item_returns_not_found(): void
    {
        $this->get(route('items.show', 999999))->assertNotFound();
    }
}
