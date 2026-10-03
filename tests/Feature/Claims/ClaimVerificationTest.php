<?php

namespace Tests\Feature\Claims;

use App\Enums\ClaimStatus;
use App\Enums\ItemStatus;
use App\Enums\PickupCodeStatus;
use App\Models\Claim;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class ClaimVerificationTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function a_correct_answer_verifies_the_claim_and_issues_a_pickup_code(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->actingAs($claimant)
            ->post(route('claims.store', $item), ['answer' => 'stiker biru di dalam'])
            ->assertRedirect();

        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->assertSame(ClaimStatus::Approved, $claim->status);
        $this->assertTrue($claim->is_verified);
        $this->assertNotNull($claim->verified_at);
        $this->assertNotNull($claim->pickupCode);
        $this->assertSame(PickupCodeStatus::Active, $claim->pickupCode->status);
    }

    #[Test]
    public function a_verified_claim_moves_the_item_to_ready_for_pickup(): void
    {
        $item = $this->storedItem();

        $this->verifyClaim($item, $this->student());

        $this->assertSame(ItemStatus::ReadyForPickup, $item->refresh()->status);
    }

    #[Test]
    public function the_correct_answer_is_matched_without_case_or_spacing_sensitivity(): void
    {
        $item = $this->storedItem();

        $this->verifyClaim($item, $this->student(), '  STIKER   Biru Di Dalam  ');

        $this->assertSame(ItemStatus::ReadyForPickup, $item->refresh()->status);
    }

    #[Test]
    public function a_wrong_answer_does_not_verify_and_does_not_issue_a_code(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->actingAs($claimant)
            ->post(route('claims.store', $item), ['answer' => 'stiker merah'])
            ->assertSessionHasErrors('answer');

        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->assertFalse($claim->is_verified);
        $this->assertSame(ClaimStatus::Submitted, $claim->status);
        $this->assertNull($claim->pickupCode);
        $this->assertSame(ItemStatus::Stored, $item->refresh()->status);
    }

    #[Test]
    public function the_error_message_tells_the_claimant_how_many_attempts_remain(): void
    {
        $item = $this->storedItem();

        $this->actingAs($this->student())
            ->post(route('claims.store', $item), ['answer' => 'salah'])
            ->assertSessionHasErrors('answer');

        $this->assertStringContainsString('Sisa percobaan: 2', session('errors')->first('answer'));
    }

    #[Test]
    public function the_claim_is_rejected_after_the_maximum_number_of_attempts(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($claimant)->post(route('claims.store', $item), ['answer' => 'salah']);
        }

        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->assertSame(ClaimStatus::Rejected, $claim->status);
        $this->assertSame(3, $claim->attempt_count);
        $this->assertNotNull($claim->rejection_reason);
    }

    #[Test]
    public function a_rejected_claimant_cannot_keep_trying(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        Claim::factory()->create([
            'item_id' => $item->id,
            'user_id' => $claimant->id,
            'status' => ClaimStatus::Rejected,
            'attempt_count' => 3,
            'rejected_at' => now(),
        ]);

        $this->actingAs($claimant)
            ->post(route('claims.store', $item), ['answer' => 'stiker biru di dalam'])
            ->assertSessionHasErrors('answer');

        $this->assertNull($item->claims()->where('user_id', $claimant->id)->first()->pickupCode);
    }

    #[Test]
    public function a_student_cannot_claim_their_own_item(): void
    {
        $reporter = $this->student();
        $item = $this->storedItem([], $reporter);

        $this->actingAs($reporter)->get(route('claims.create', $item))->assertForbidden();

        $this->actingAs($reporter)
            ->post(route('claims.store', $item), ['answer' => 'stiker biru di dalam'])
            ->assertForbidden();
    }

    #[Test]
    public function an_item_under_claim_cannot_be_claimed_by_someone_else(): void
    {
        $item = $this->storedItem();

        // First claimant verifies and the item becomes unavailable.
        $this->verifyClaim($item, $this->student());

        $this->actingAs($this->student())
            ->get(route('claims.create', $item->refresh()))
            ->assertForbidden();
    }

    #[Test]
    public function a_student_cannot_open_a_second_parallel_claim_on_the_same_item(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->actingAs($claimant)->post(route('claims.store', $item), ['answer' => 'salah']);
        $this->actingAs($claimant)->post(route('claims.store', $item), ['answer' => 'salah']);

        $this->assertSame(1, $item->claims()->where('user_id', $claimant->id)->count());
        $this->assertSame(2, $item->claims()->where('user_id', $claimant->id)->first()->attempt_count);
    }

    #[Test]
    public function the_claimant_can_open_their_pickup_page_with_the_code(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $plain = $this->verifyClaim($item, $claimant);
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        // Halaman kode dilindungi konfirmasi password.
        $this->actingAs($claimant)
            ->get(route('claims.pickup', $claim))
            ->assertRedirect(route('password.confirm'));

        $this->actingAs($claimant)
            ->withConfirmedPassword()
            ->get(route('claims.pickup', $claim))
            ->assertOk()
            ->assertSee($plain, escape: false)
            ->assertSee('Verifikasi Berhasil');
    }

    #[Test]
    public function an_approved_claimant_visiting_the_claim_form_is_redirected_to_the_code(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->actingAs($claimant)
            ->get(route('claims.create', $item->refresh()))
            ->assertRedirect(route('claims.pickup', $claim));
    }

    #[Test]
    public function the_claim_form_shows_the_question_but_never_the_answer(): void
    {
        $item = $this->storedItem([
            'verification_question' => 'Apa warna stikernya?',
            'verification_answer' => Item::normalizeAnswer('stiker biru di dalam'),
        ]);

        $this->actingAs($this->student())
            ->get(route('claims.create', $item))
            ->assertOk()
            ->assertSee('Apa warna stikernya?')
            ->assertDontSee('biru di dalam');
    }

    #[Test]
    public function claim_verification_is_rate_limited(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($claimant)->post(route('claims.store', $item), ['answer' => 'salah']);
        }

        $this->actingAs($claimant)
            ->post(route('claims.store', $item), ['answer' => 'stiker biru di dalam'])
            ->assertStatus(429);
    }

    #[Test]
    public function the_claim_answer_is_never_written_to_the_database(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->actingAs($claimant)
            ->post(route('claims.store', $item), ['answer' => 'stiker biru di dalam']);

        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->assertArrayNotHasKey('answer', $claim->getAttributes());
        $this->assertStringNotContainsString('stiker biru', json_encode($claim->getAttributes()));
    }

    #[Test]
    public function repeated_claims_do_not_accumulate_extra_pickup_codes(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);

        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->assertSame(1, $claim->pickupCodes()->count());
        $this->assertSame(1, $item->pickupCodes()->count());
        $this->assertNotNull($claim->pickupCode);
    }
}
