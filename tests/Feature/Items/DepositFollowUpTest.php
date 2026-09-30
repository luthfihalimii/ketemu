<?php

namespace Tests\Feature\Items;

use App\Enums\ItemStatus;
use App\Models\AuditLog;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

/**
 * A finder who never confirms the handover leaves a report hanging. The item
 * may already be sitting at the security post, so the report is flagged for a
 * human to chase rather than closed on a timer.
 */
class DepositFollowUpTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    private function pendingDeposit(array $attributes = []): Item
    {
        return Item::factory()->create(array_merge([
            'status' => ItemStatus::WaitingDeposit,
            'deposit_location_id' => Location::factory()->securityPost()->create()->id,
        ], $attributes));
    }

    #[Test]
    public function a_report_past_the_window_is_flagged_but_never_closed(): void
    {
        $overdue = $this->pendingDeposit(['created_at' => now()->subDays(3)]);

        $this->artisan('ketemupens:expire')->assertSuccessful();

        $overdue->refresh();

        // Marked for follow-up...
        $this->assertNotNull($overdue->deposit_reminded_at);
        // ...but still waiting, because the item may be at the post.
        $this->assertSame(ItemStatus::WaitingDeposit, $overdue->status);
        $this->assertTrue($overdue->isDepositOverdue());

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'items.deposit_follow_up',
            'auditable_id' => $overdue->id,
        ]);
    }

    #[Test]
    public function a_report_inside_the_window_is_left_alone(): void
    {
        $fresh = $this->pendingDeposit(['created_at' => now()->subHour()]);

        $this->artisan('ketemupens:expire')->assertSuccessful();

        $this->assertNull($fresh->refresh()->deposit_reminded_at);
        $this->assertFalse($fresh->isDepositOverdue());
    }

    #[Test]
    public function the_flag_is_written_only_once_across_repeated_runs(): void
    {
        $overdue = $this->pendingDeposit(['created_at' => now()->subDays(5)]);

        $this->artisan('ketemupens:expire')->assertSuccessful();
        $markedAt = $overdue->refresh()->deposit_reminded_at;

        $this->artisan('ketemupens:expire')->assertSuccessful();

        $this->assertSame(
            1,
            AuditLog::query()->where('event', 'items.deposit_follow_up')->count(),
        );
        $this->assertEquals($markedAt, $overdue->refresh()->deposit_reminded_at);
    }

    #[Test]
    public function a_report_already_confirmed_is_not_flagged(): void
    {
        $confirmed = $this->storedItem();
        $confirmed->update(['created_at' => now()->subDays(10)]);

        $this->artisan('ketemupens:expire')->assertSuccessful();

        $this->assertNull($confirmed->refresh()->deposit_reminded_at);
        $this->assertFalse($confirmed->isDepositOverdue());
    }

    #[Test]
    public function confirming_the_deposit_clears_the_follow_up(): void
    {
        $owner = $this->student();
        $item = $this->pendingDeposit(['user_id' => $owner->id, 'created_at' => now()->subDays(3)]);

        $this->artisan('ketemupens:expire')->assertSuccessful();
        $this->assertTrue($item->refresh()->isDepositOverdue());

        $this->actingAs($owner)
            ->post(route('items.confirm-deposit', $item))
            ->assertRedirect();

        $this->assertSame(ItemStatus::Stored, $item->refresh()->status);
        $this->assertFalse($item->isDepositOverdue());
    }

    #[Test]
    public function the_finder_sees_the_reminder_on_their_reports_page(): void
    {
        $owner = $this->student();
        $overdue = $this->pendingDeposit([
            'user_id' => $owner->id,
            'title' => 'Jaket Tertinggal Di Kelas',
            'created_at' => now()->subDays(3),
        ]);

        $this->artisan('ketemupens:expire')->assertSuccessful();

        $this->actingAs($owner)
            ->get(route('dashboard.reports'))
            ->assertOk()
            ->assertSee('Perlu ditindaklanjuti')
            ->assertSee($overdue->title);
    }

    #[Test]
    public function an_admin_can_filter_the_queue_to_unconfirmed_deposits(): void
    {
        $overdue = $this->pendingDeposit([
            'title' => 'Dompet Belum Dititipkan',
            'created_at' => now()->subDays(3),
        ]);
        $fresh = $this->pendingDeposit([
            'title' => 'Barang Masih Baru',
            'created_at' => now()->subHour(),
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.items.index', ['only' => 'deposit_overdue']))
            ->assertOk()
            ->assertSee($overdue->title)
            ->assertDontSee($fresh->title);
    }

    #[Test]
    public function an_admin_cannot_use_an_unknown_filter(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.items.index', ['only' => 'anything_else']))
            ->assertSessionHasErrors('only');
    }
}
