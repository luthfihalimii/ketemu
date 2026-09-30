<?php

namespace Tests\Feature\Claims;

use App\Enums\ClaimStatus;
use App\Enums\ItemStatus;
use App\Enums\PickupCodeStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class PickupFlowTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function security_staff_can_redeem_a_valid_code_and_release_the_item(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();
        $guard = User::factory()->guard()->create();

        $plain = $this->verifyClaim($item, $claimant);

        $this->redeem($plain, $guard)
            ->assertRedirect(route('guard.pickup.create'));

        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();
        $code = $claim->pickupCode;

        $this->assertSame(PickupCodeStatus::Used, $code->status);
        $this->assertNotNull($code->used_at);
        $this->assertSame($guard->id, $code->verified_by);
        $this->assertSame(ClaimStatus::Completed, $claim->status);
        $this->assertSame(ItemStatus::Returned, $item->refresh()->status);
        $this->assertNotNull($item->returned_at);
    }

    #[Test]
    public function the_recipient_identity_is_recorded_on_the_code_and_the_audit_log(): void
    {
        $item = $this->storedItem();
        $guard = User::factory()->guard()->create();
        $plain = $this->verifyClaim($item, $this->student());

        $this->redeem($plain, $guard, '2141720099', 'Budi Santoso')
            ->assertRedirect();

        $code = $item->claims()->firstOrFail()->pickupCode;

        $this->assertSame('2141720099', $code->recipient_id_number);
        $this->assertSame('Budi Santoso', $code->recipient_name);

        $log = AuditLog::query()->where('event', 'pickup.completed')->firstOrFail();

        $this->assertSame('2141720099', $log->properties['recipient_id_number']);
        $this->assertSame('Budi Santoso', $log->properties['recipient_name']);
    }

    #[Test]
    public function the_verification_panel_asks_for_the_recipient_identity(): void
    {
        $this->actingAs(User::factory()->guard()->create())
            ->get(route('guard.pickup.create'))
            ->assertOk()
            ->assertSee('name="recipient_id_number"', escape: false)
            ->assertSee('name="recipient_name"', escape: false);
    }

    #[Test]
    public function a_handover_requires_the_recipient_identity(): void
    {
        $item = $this->storedItem();
        $guard = User::factory()->guard()->create();
        $plain = $this->verifyClaim($item, $this->student());

        $this->actingAs($guard)
            ->post(route('guard.pickup.store'), ['code' => $plain])
            ->assertSessionHasErrors(['recipient_id_number', 'recipient_name']);

        // The item must not be released without a recorded recipient.
        $this->assertSame(ItemStatus::ReadyForPickup, $item->refresh()->status);
        $this->assertSame(PickupCodeStatus::Active, $item->claims()->firstOrFail()->pickupCode->refresh()->status);
    }

    #[Test]
    public function the_code_works_once_only(): void
    {
        $item = $this->storedItem();
        $guard = User::factory()->guard()->create();
        $plain = $this->verifyClaim($item, $this->student());

        $this->redeem($plain, $guard);

        $this->redeem($plain, $guard)->assertSessionHasErrors('code');
    }

    #[Test]
    public function a_code_that_has_expired_is_rejected(): void
    {
        $item = $this->storedItem();
        $guard = User::factory()->guard()->create();
        $claimant = $this->student();

        $plain = $this->verifyClaim($item, $claimant);

        $item->claims()->where('user_id', $claimant->id)->firstOrFail()
            ->pickupCode()->update(['expires_at' => now()->subMinute()]);

        $this->redeem($plain, $guard)->assertSessionHasErrors('code');

        $this->assertSame(ItemStatus::ReadyForPickup, $item->refresh()->status);
    }

    #[Test]
    public function an_unknown_code_is_rejected(): void
    {
        $guard = User::factory()->guard()->create();

        $this->redeem('AAAA-BBBB', $guard)->assertSessionHasErrors('code');
    }

    #[Test]
    public function guards_may_enter_the_code_in_any_formatting(): void
    {
        $item = $this->storedItem();
        $guard = User::factory()->guard()->create();
        $plain = $this->verifyClaim($item, $this->student());

        $messy = strtolower(str_replace('-', ' ', $plain));

        $this->redeem($messy, $guard)->assertRedirect(route('guard.pickup.create'));

        $this->assertSame(ItemStatus::Returned, $item->refresh()->status);
    }

    #[Test]
    public function an_admin_can_also_verify_a_code(): void
    {
        $item = $this->storedItem();
        $admin = User::factory()->admin()->create();
        $plain = $this->verifyClaim($item, $this->student());

        $this->redeem($plain, $admin)->assertRedirect(route('guard.pickup.create'));
    }

    #[Test]
    public function a_student_cannot_open_the_verification_panel(): void
    {
        $this->actingAs($this->student())
            ->get(route('guard.pickup.create'))
            ->assertForbidden();

        $this->actingAs($this->student())
            ->post(route('guard.pickup.store'), ['code' => 'AAAA-BBBB'])
            ->assertForbidden();
    }

    #[Test]
    public function guests_cannot_open_the_verification_panel(): void
    {
        $this->get(route('guard.pickup.create'))->assertRedirect(route('login'));
    }

    #[Test]
    public function pickup_verification_is_rate_limited(): void
    {
        $guard = User::factory()->guard()->create();

        for ($i = 0; $i < 15; $i++) {
            $this->redeem('AAAA-BBBB', $guard);
        }

        $this->redeem('AAAA-BBBB', $guard)->assertStatus(429);
    }

    #[Test]
    public function the_student_can_cancel_an_open_claim_and_the_item_returns_to_the_shelf(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->actingAs($claimant)
            ->delete(route('claims.cancel', $claim))
            ->assertRedirect();

        $this->assertSame(ClaimStatus::Cancelled, $claim->refresh()->status);
        $this->assertSame(ItemStatus::Stored, $item->refresh()->status);
        $this->assertSame(PickupCodeStatus::Cancelled, $claim->pickupCode->refresh()->status);
    }

    #[Test]
    public function a_student_cannot_cancel_someone_elses_claim(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->actingAs($this->student())
            ->delete(route('claims.cancel', $claim))
            ->assertForbidden();

        $this->assertSame(ClaimStatus::Approved, $claim->refresh()->status);
    }

    #[Test]
    public function the_audit_trail_records_the_whole_flow(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();
        $guard = User::factory()->guard()->create();

        $plain = $this->verifyClaim($item, $claimant);
        $this->redeem($plain, $guard);

        $this->assertDatabaseHas('audit_logs', ['event' => 'claim.verified']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'pickup.completed']);
    }

    #[Test]
    public function the_audit_log_never_stores_the_pickup_code_or_answer(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();
        $guard = User::factory()->guard()->create();

        $plain = $this->verifyClaim($item, $claimant, 'stiker biru di dalam');
        $this->redeem($plain, $guard);

        $all = json_encode(AuditLog::query()->pluck('properties'));

        $this->assertStringNotContainsString($plain, (string) $all);
        $this->assertStringNotContainsString('stiker biru di dalam', (string) $all);
    }

    #[Test]
    public function a_guard_cannot_redeem_a_code_that_was_never_issued(): void
    {
        $this->storedItem();
        $guard = User::factory()->guard()->create();

        $this->redeem('', $guard)->assertSessionHasErrors('code');
    }

    #[Test]
    public function only_guards_and_admins_have_the_verification_capability(): void
    {
        $this->assertTrue(User::factory()->guard()->create()->canVerifyPickup());
        $this->assertTrue(User::factory()->admin()->create()->canVerifyPickup());
        $this->assertFalse(User::factory()->create(['role' => Role::Student])->canVerifyPickup());
    }
}
