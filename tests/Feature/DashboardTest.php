<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\ItemStatus;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function a_student_sees_only_their_own_reports_and_claims(): void
    {
        $student = $this->student();
        $other = $this->student();

        $this->storedItem(['title' => 'Laporan Milikku'], $student);
        $this->storedItem(['title' => 'Laporan Orang Lain'], $other);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Laporan Milikku')
            ->assertDontSee('Laporan Orang Lain');

        $this->actingAs($student)
            ->get(route('dashboard.reports'))
            ->assertOk()
            ->assertSee('Laporan Milikku')
            ->assertDontSee('Laporan Orang Lain');
    }

    #[Test]
    public function the_dashboard_surfaces_a_claim_that_is_ready_for_pickup(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);

        $this->actingAs($claimant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Siap diambil');
    }

    #[Test]
    public function the_report_list_offers_the_deposit_confirmation_while_waiting(): void
    {
        $student = $this->student();
        $this->storedItem(['status' => ItemStatus::WaitingDeposit, 'stored_at' => null], $student);

        $this->actingAs($student)
            ->get(route('dashboard.reports'))
            ->assertOk()
            ->assertSee('Sudah dititipkan');
    }

    #[Test]
    public function the_claim_list_shows_remaining_attempts_for_a_failed_attempt(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->actingAs($claimant)->post(route('claims.store', $item), ['answer' => 'salah']);

        $this->actingAs($claimant)
            ->get(route('dashboard.claims'))
            ->assertOk()
            ->assertSee('Sisa percobaan: 2 dari 3');
    }

    #[Test]
    public function the_report_code_is_shown_for_tracking(): void
    {
        $student = $this->student();
        $item = Item::factory()->create(['user_id' => $student->id]);

        $this->actingAs($student)
            ->get(route('dashboard.reports'))
            ->assertOk()
            ->assertSee($item->code);
    }

    #[Test]
    public function a_claimant_can_cancel_their_own_claim_and_the_item_returns_to_the_shelf(): void
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
    }

    #[Test]
    public function a_student_cannot_cancel_another_students_claim(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->actingAs($this->student())
            ->delete(route('claims.cancel', $claim))
            ->assertForbidden();
    }
}
