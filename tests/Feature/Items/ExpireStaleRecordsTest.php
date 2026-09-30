<?php

namespace Tests\Feature\Items;

use App\Enums\ItemStatus;
use App\Enums\PickupCodeStatus;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class ExpireStaleRecordsTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function a_stored_item_past_the_shelf_window_is_expired(): void
    {
        $stale = $this->storedItem([
            'title' => 'Payung Tertinggal',
            'stored_at' => now()->subDays(400),
        ]);

        // A fresh item must survive the sweep.
        $fresh = $this->storedItem(['title' => 'Tumbler Baru']);

        $this->artisan('ketemupens:expire')->assertSuccessful();

        $this->assertSame(ItemStatus::Expired, $stale->refresh()->status);
        $this->assertNotNull($stale->expires_at);
        $this->assertSame(ItemStatus::Stored, $fresh->refresh()->status);

        $this->get(route('items.index'))->assertOk()->assertDontSee('Payung Tertinggal');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'items.expired_automatically',
            'auditable_id' => $stale->id,
        ]);
    }

    #[Test]
    public function an_item_that_is_not_stored_is_never_swept(): void
    {
        $returned = $this->storedItem([
            'status' => ItemStatus::Returned,
            'stored_at' => now()->subDays(400),
        ]);

        $this->artisan('ketemupens:expire')->assertSuccessful();

        $this->assertSame(ItemStatus::Returned, $returned->refresh()->status);
    }

    #[Test]
    public function an_overdue_pickup_code_is_marked_expired(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);
        $code = $item->claims()->firstOrFail()->pickupCode;

        $code->update(['expires_at' => now()->subMinute()]);

        $this->assertSame(PickupCodeStatus::Active, $code->refresh()->status, 'still active until swept');

        $this->artisan('ketemupens:expire')->assertSuccessful();

        $this->assertSame(PickupCodeStatus::Expired, $code->refresh()->status);
    }
}
