<?php

namespace Tests\Feature\Items;

use App\Enums\ClaimStatus;
use App\Enums\ItemStatus;
use App\Enums\PickupCodeStatus;
use App\Models\Item;
use App\Notifications\ActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
    public function an_approved_claim_past_the_grace_window_is_auto_released(): void
    {
        Notification::fake();

        $item = $this->storedItem();
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        // Kode dibiarkan kedaluwarsa jauh melewati masa tenggang (3 hari).
        $claim->pickupCode()->update([
            'status' => PickupCodeStatus::Expired,
            'expires_at' => now()->subDays(10),
        ]);

        $this->artisan('ketemupens:expire')->assertSuccessful();

        // Klaim dilepas dan barang kembali ke rak; kode yang sudah
        // kedaluwarsa tetap berstatus EXPIRED (hanya kode aktif yang dibatalkan).
        $this->assertSame(ClaimStatus::Cancelled, $claim->refresh()->status);
        $this->assertFalse($claim->is_verified);
        $this->assertSame(ItemStatus::Stored, $item->refresh()->status);

        Notification::assertSentTo(
            $claimant,
            ActivityNotification::class,
            fn ($notification) => $notification->event === ActivityNotification::CLAIM_AUTO_RELEASED,
        );
    }

    #[Test]
    public function a_claim_still_inside_the_grace_window_is_not_released(): void
    {
        Notification::fake();

        $item = $this->storedItem();
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        // Baru kedaluwarsa kemarin; masih dalam masa tenggang 3 hari.
        $claim->pickupCode()->update([
            'status' => PickupCodeStatus::Expired,
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('ketemupens:expire')->assertSuccessful();

        $this->assertSame(ClaimStatus::Approved, $claim->refresh()->status);

        Notification::assertNotSentTo(
            $claimant,
            ActivityNotification::class,
            fn ($notification) => $notification->event === ActivityNotification::CLAIM_AUTO_RELEASED,
        );
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
