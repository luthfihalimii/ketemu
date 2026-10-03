<?php

namespace Tests\Feature\Notifications;

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Services\ModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

/**
 * In-app notifications only. The mailer is the log driver, so nothing claims
 * to send email yet; these cover the database channel and its inbox.
 */
class NotificationTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function an_approved_claim_notifies_the_claimant(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);

        $this->assertSame(1, $claimant->notifications()->count());

        $data = $claimant->notifications()->firstOrFail()->data;

        $this->assertSame('claim.verified', $data['event']);
        $this->assertSame($item->id, $data['item_id']);
    }

    #[Test]
    public function a_failed_attempt_notifies_the_claimant_with_remaining_tries(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->actingAs($claimant)
            ->post(route('claims.store', $item), ['answer' => 'jawaban salah'])
            ->assertRedirect();

        $notification = $claimant->notifications()->firstOrFail();

        $this->assertSame('claim.failed', $notification->data['event']);
        $this->assertSame('warning', $notification->data['level']);
    }

    #[Test]
    public function an_admin_rejection_notifies_the_claimant(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();
        $admin = User::factory()->admin()->create();

        $this->actingAs($claimant)
            ->post(route('claims.store', $item), ['answer' => 'jawaban salah'])
            ->assertRedirect();

        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();
        $claimant->notifications()->delete();

        app(ModerationService::class)->rejectClaim($claim, 'Bukti tidak cukup', $admin);

        $notification = $claimant->notifications()->firstOrFail();

        $this->assertSame('claim.rejected', $notification->data['event']);
        $this->assertStringContainsString('Bukti tidak cukup', $notification->data['body']);
    }

    #[Test]
    public function a_taken_down_report_notifies_the_owner(): void
    {
        $owner = $this->student();
        $item = $this->storedItem([], $owner);
        $admin = User::factory()->admin()->create();

        app(ModerationService::class)->reject($item, 'Laporan palsu', $admin);

        $notification = $owner->notifications()->firstOrFail();

        $this->assertSame('claim.rejected', $notification->data['event']);
        $this->assertStringContainsString('Laporan palsu', $notification->data['body']);
    }

    #[Test]
    public function an_unconfirmed_deposit_notifies_the_finder(): void
    {
        $owner = $this->student();
        $item = Item::factory()->create([
            'user_id' => $owner->id,
            'status' => ItemStatus::WaitingDeposit,
            'deposit_location_id' => Location::factory()->securityPost()->create()->id,
            'created_at' => now()->subDays(3),
        ]);

        $this->artisan('ketemupens:expire')->assertSuccessful();

        $notification = $owner->notifications()->firstOrFail();

        $this->assertSame('item.deposit_unconfirmed', $notification->data['event']);
        $this->assertSame('warning', $notification->data['level']);
    }

    #[Test]
    public function handing_the_item_over_notifies_the_linked_lost_report_owner(): void
    {
        $lostOwner = $this->student();
        $lost = Item::factory()->create([
            'user_id' => $lostOwner->id,
            'status' => ItemStatus::Reported,
            'deposit_location_id' => null,
        ]);

        $found = $this->storedItem();
        $lost->matchTo($found);

        $code = $this->verifyClaim($found, $lostOwner);
        $this->redeem($code, User::factory()->guard()->create())->assertRedirect();

        $lostOwner->refresh();

        $this->assertTrue(
            $lostOwner->notifications()->get()->contains(
                fn ($n) => $n->data['event'] === 'item.returned',
            ),
        );
    }

    #[Test]
    public function the_bell_badge_counts_unread_notifications(): void
    {
        $user = $this->student();
        $user->notify($this->sample($user));

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Notifikasi');
    }

    #[Test]
    public function a_student_cannot_open_another_students_notification(): void
    {
        $owner = $this->student();
        $owner->notify($this->sample($owner));

        $foreign = $owner->notifications()->firstOrFail();

        $this->actingAs($this->student())
            ->get(route('notifications.show', $foreign->id))
            ->assertNotFound();
    }

    #[Test]
    public function a_notification_with_an_external_url_redirects_to_the_dashboard_instead(): void
    {
        $user = $this->student();

        $user->notify(new ActivityNotification(
            event: 'test.open_redirect',
            title: 'Uji redirect',
            body: 'Notifikasi dengan URL jahat.',
            url: 'https://evil.com/phishing',
        ));

        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('notifications.show', $notification->id))
            ->assertRedirect(route('dashboard'));
    }

    #[Test]
    public function a_notification_with_a_protocol_relative_url_is_also_blocked(): void
    {
        $user = $this->student();

        $user->notify(new ActivityNotification(
            event: 'test.open_redirect',
            title: 'Uji redirect',
            body: 'Notifikasi dengan URL protokol-relatif.',
            url: '//evil.com/phishing',
        ));

        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('notifications.show', $notification->id))
            ->assertRedirect(route('dashboard'));
    }

    #[Test]
    public function following_a_notification_marks_it_read_and_redirects_to_its_subject(): void
    {
        $user = $this->student();
        $item = $this->storedItem([], $user);

        $user->notify($this->sample($user, $item));

        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('notifications.show', $notification->id))
            ->assertRedirect(route('items.show', $item));

        $this->assertNotNull($notification->refresh()->read_at);
    }

    #[Test]
    public function a_student_can_mark_everything_as_read(): void
    {
        $user = $this->student();
        $user->notify($this->sample($user));
        $user->notify($this->sample($user));

        $this->assertSame(2, $user->unreadNotifications()->count());

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    #[Test]
    public function the_inbox_lists_notifications(): void
    {
        $user = $this->student();
        $user->notify($this->sample($user));

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Klaim disetujui');
    }

    #[Test]
    public function guests_cannot_reach_the_inbox(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    private function sample(User $user, ?Item $item = null): ActivityNotification
    {
        return new ActivityNotification(
            event: 'claim.verified',
            title: 'Klaim disetujui',
            body: 'Kode pengambilan sudah terbit.',
            item: $item,
            url: $item ? route('items.show', $item) : route('dashboard'),
            level: 'success',
        );
    }
}
