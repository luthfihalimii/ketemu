<?php

namespace Tests\Feature\Notifications;

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use App\Services\ClaimVerificationService;
use App\Services\ModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

/**
 * Three admin-facing events: a flagged report, an unconfirmed deposit, and a
 * claim that ran out of verification attempts. The last one is the borderline
 * case a human has to look at.
 */
class AdminNotificationTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function flagging_a_report_notifies_every_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $guard = User::factory()->guard()->create();
        $item = $this->storedItem();

        app(ModerationService::class)->flag($item, 'Foto tidak sesuai', $admin);

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(1, $otherAdmin->notifications()->count());
        // Guards are not moderators.
        $this->assertSame(0, $guard->notifications()->count());
    }

    #[Test]
    public function the_flag_notification_carries_the_reason_and_event(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem();

        app(ModerationService::class)->flag($item, 'Laporan ganda', $admin);

        $data = $admin->notifications()->firstOrFail()->data;

        $this->assertSame('admin.item_flagged', $data['event']);
        $this->assertStringContainsString('Laporan ganda', $data['body']);
    }

    #[Test]
    public function an_unconfirmed_deposit_notifies_the_finder_and_the_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = $this->student();

        Item::factory()->create([
            'user_id' => $owner->id,
            'status' => ItemStatus::WaitingDeposit,
            'deposit_location_id' => Location::factory()->securityPost()->create()->id,
            'created_at' => now()->subDays(3),
        ]);

        $this->artisan('ketemupens:expire')->assertSuccessful();

        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame(1, $admin->notifications()->count());

        $this->assertSame(
            'admin.deposit_unconfirmed',
            $admin->notifications()->firstOrFail()->data['event'],
        );
    }

    #[Test]
    public function the_scheduler_does_not_notify_admins_again_on_the_next_run(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = $this->student();

        Item::factory()->create([
            'user_id' => $owner->id,
            'status' => ItemStatus::WaitingDeposit,
            'deposit_location_id' => Location::factory()->securityPost()->create()->id,
            'created_at' => now()->subDays(3),
        ]);

        $this->artisan('ketemupens:expire')->assertSuccessful();
        $this->artisan('ketemupens:expire')->assertSuccessful();

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(1, $owner->notifications()->count());
    }

    #[Test]
    public function exhausting_the_verification_attempts_notifies_the_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem();
        $claimant = $this->student();

        // One short of the limit, then the final wrong answer.
        for ($i = 0; $i < ClaimVerificationService::MAX_ATTEMPTS; $i++) {
            $this->actingAs($claimant)
                ->post(route('claims.store', $item), ['answer' => 'jawaban salah '.$i]);
        }

        $notifications = $admin->notifications()->get();

        $this->assertTrue(
            $notifications->contains(fn ($n) => $n->data['event'] === 'admin.claim_attempts_exhausted'),
        );
    }

    #[Test]
    public function a_wrong_answer_that_still_has_attempts_left_does_not_alert_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->actingAs($claimant)
            ->post(route('claims.store', $item), ['answer' => 'salah'])
            ->assertRedirect();

        $this->assertSame(0, $admin->notifications()->count());
        // The student still gets their own feedback.
        $this->assertSame(1, $claimant->notifications()->count());
    }

    #[Test]
    public function admins_are_only_notified_for_their_own_role(): void
    {
        $this->assertSame(0, User::admins()->count());

        User::factory()->admin()->create();
        User::factory()->guard()->create();
        User::factory()->create();

        $this->assertSame(1, User::admins()->count());
    }
}
