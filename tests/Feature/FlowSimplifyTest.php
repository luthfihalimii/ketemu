<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\Role;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class FlowSimplifyTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function guard_confirms_deposit_in_one_step_via_report_code(): void
    {
        $reporter = $this->student();
        $guard = User::factory()->create(['role' => Role::Guard]);
        $item = Item::factory()->create([
            'user_id' => $reporter->id,
            'status' => ItemStatus::WaitingDeposit,
        ]);

        $this->actingAs($guard)->post(route('guard.deposit.confirm-code'), [
            'deposit_code' => strtolower($item->code),
        ])->assertSessionHas('status');

        $item->refresh();
        $this->assertSame(ItemStatus::Stored, $item->status);
        $this->assertNotNull($item->deposit_requested_at);
        $this->assertNotNull($item->deposit_confirmed_at);
        $this->assertSame($guard->id, $item->deposit_confirmed_by);
    }

    #[Test]
    public function guard_deposit_code_rejects_unknown_codes(): void
    {
        $guard = User::factory()->create(['role' => Role::Guard]);

        $this->actingAs($guard)->post(route('guard.deposit.confirm-code'), [
            'deposit_code' => 'KP-TIDAK-ADA',
        ])->assertSessionHasErrors('deposit_code');
    }

    #[Test]
    public function owner_sees_a_deposit_qr_while_waiting(): void
    {
        $reporter = $this->student();
        $item = Item::factory()->create([
            'user_id' => $reporter->id,
            'status' => ItemStatus::WaitingDeposit,
        ]);

        $this->actingAs($reporter)->get(route('items.show', $item))
            ->assertOk()->assertSee('QR titip', false);

        $this->actingAs($reporter)->get(route('dashboard.reports'))
            ->assertOk()->assertSee('Tunjukkan ke satpam', false);
    }

    #[Test]
    public function lost_report_matching_is_presented_as_optional(): void
    {
        $owner = $this->student();
        $lost = Item::factory()->lost()->create(['user_id' => $owner->id]);

        // Klaim langsung tanpa menautkan tetap dimungkinkan: tautan opsional.
        $this->assertNull($lost->matched_item_id);

        $this->actingAs($owner)->get(route('dashboard.reports'))
            ->assertOk()->assertSee('opsional', false);
    }
}
