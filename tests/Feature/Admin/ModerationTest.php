<?php

namespace Tests\Feature\Admin;

use App\Enums\ClaimStatus;
use App\Enums\ItemStatus;
use App\Enums\PickupCodeStatus;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function only_admins_can_reach_the_moderation_queue(): void
    {
        $this->get(route('admin.items.index'))->assertRedirect(route('login'));

        $this->actingAs($this->student())->get(route('admin.items.index'))->assertForbidden();
        $this->actingAs(User::factory()->guard()->create())->get(route('admin.items.index'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.items.index'))->assertOk();
    }

    #[Test]
    public function an_admin_can_flag_a_suspicious_report_with_a_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem();

        $this->actingAs($admin)
            ->post(route('admin.items.flag', $item), ['reason' => 'Deskripsi mencurigakan'])
            ->assertRedirect();

        $item->refresh();

        $this->assertTrue($item->isFlagged());
        $this->assertSame('Deskripsi mencurigakan', $item->flag_reason);
        $this->assertSame($admin->id, $item->moderated_by);
        $this->assertDatabaseHas('audit_logs', ['event' => 'moderation.item_flagged']);
    }

    #[Test]
    public function flagging_requires_a_reason_for_the_audit_trail(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem();

        $this->actingAs($admin)
            ->post(route('admin.items.flag', $item), ['reason' => 'x'])
            ->assertSessionHasErrors('reason');

        $this->assertFalse($item->refresh()->isFlagged());
    }

    #[Test]
    public function an_admin_can_remove_a_flag(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem();
        $item->update(['flagged_at' => now(), 'flag_reason' => 'awal']);

        $this->actingAs($admin)->delete(route('admin.items.unflag', $item))->assertRedirect();

        $this->assertFalse($item->refresh()->isFlagged());
        $this->assertNull($item->flag_reason);
    }

    #[Test]
    public function rejecting_a_report_takes_it_out_of_the_public_catalogue(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem(['title' => 'Laporan Palsu']);

        $this->actingAs($admin)
            ->post(route('admin.items.reject', $item), ['reason' => 'Laporan palsu'])
            ->assertRedirect();

        $item->refresh();

        $this->assertSame(ItemStatus::Rejected, $item->status);
        $this->assertFalse($item->isPubliclyAvailable());

        $this->get(route('items.index'))->assertOk()->assertDontSee('Laporan Palsu');
    }

    #[Test]
    public function rejecting_a_report_cancels_active_claims_and_live_codes(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.items.reject', $item), ['reason' => 'Barang tidak pernah dititipkan']);

        $this->assertSame(ClaimStatus::Cancelled, $claim->refresh()->status);
        $this->assertSame(PickupCodeStatus::Cancelled, $claim->pickupCode->refresh()->status);
        $this->assertSame(ItemStatus::Rejected, $item->refresh()->status);
    }

    #[Test]
    public function a_rejected_report_cannot_be_rejected_again(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem(['status' => ItemStatus::Rejected]);

        $this->actingAs($admin)
            ->post(route('admin.items.reject', $item), ['reason' => 'Lagi-lagi palsu'])
            ->assertSessionHasErrors('reason');
    }

    #[Test]
    public function an_admin_can_restore_a_rejected_report(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem(['status' => ItemStatus::Rejected]);

        $this->actingAs($admin)->post(route('admin.items.restore', $item))->assertRedirect();

        // A found report goes back to the shelf.
        $this->assertSame(ItemStatus::Stored, $item->refresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'moderation.item_restored']);
    }

    #[Test]
    public function a_restored_lost_report_returns_to_reported(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem([
            'status' => ItemStatus::Rejected,
            'deposit_location_id' => null,
            'stored_at' => null,
        ]);

        $this->actingAs($admin)->post(route('admin.items.restore', $item));

        $this->assertSame(ItemStatus::Reported, $item->refresh()->status);
    }

    #[Test]
    public function only_a_rejected_report_can_be_restored(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem();

        $this->actingAs($admin)
            ->post(route('admin.items.restore', $item))
            ->assertSessionHasErrors('reason');
    }

    #[Test]
    public function an_admin_can_expire_a_stale_stored_item(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem(['title' => 'Barang Lama']);

        $this->actingAs($admin)->post(route('admin.items.expire', $item))->assertRedirect();

        $this->assertSame(ItemStatus::Expired, $item->refresh()->status);
        $this->assertNotNull($item->expires_at);

        $this->get(route('items.index'))->assertOk()->assertDontSee('Barang Lama');
    }

    #[Test]
    public function a_returned_item_cannot_be_expired(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem(['status' => ItemStatus::Returned]);

        $this->actingAs($admin)
            ->post(route('admin.items.expire', $item))
            ->assertSessionHasErrors('reason');
    }

    #[Test]
    public function an_admin_can_reject_a_claim_and_the_item_returns_to_the_shelf(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.claims.reject', $claim), ['reason' => 'Bukti kepemilikan tidak cukup'])
            ->assertRedirect();

        $this->assertSame(ClaimStatus::Rejected, $claim->refresh()->status);
        $this->assertSame('Bukti kepemilikan tidak cukup', $claim->rejection_reason);
        $this->assertSame(ItemStatus::Stored, $item->refresh()->status);
        $this->assertSame(PickupCodeStatus::Cancelled, $claim->pickupCode->refresh()->status);
    }

    #[Test]
    public function a_claim_that_is_not_active_cannot_be_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem();
        $claim = Claim::factory()->create([
            'item_id' => $item->id,
            'status' => ClaimStatus::Completed,
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.claims.reject', $claim), ['reason' => 'Terlambat menolak'])
            ->assertSessionHasErrors('reason');
    }

    #[Test]
    public function an_admin_can_reissue_an_expired_pickup_code(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $old = $claim->pickupCode;
        $old->update(['expires_at' => now()->subMinute()]);

        // Notifikasi hanya menautkan halaman kode milik pemilik klaim.
        Notification::fake();

        $this->actingAs($admin)
            ->post(route('admin.claims.reissue', $claim))
            ->assertRedirect()
            ->assertSessionMissing('reissued_code');

        $new = $claim->refresh()->pickupCode;

        $this->assertNotSame($old->id, $new->id);
        $this->assertSame(PickupCodeStatus::Active, $new->status);
        $this->assertTrue($new->isUsable());
        $this->assertDatabaseHas('audit_logs', ['event' => 'moderation.code_reissued']);

        Notification::assertSentTo(
            $claimant,
            ActivityNotification::class,
            function ($notification) use ($claimant, $claim, $new): bool {
                $plain = $new->plainCode();
                $this->assertNotNull($plain);
                $this->assertStringNotContainsString($plain, serialize($notification));
                $this->assertStringNotContainsString($plain, json_encode($notification->toArray($claimant), JSON_THROW_ON_ERROR));
                $this->assertStringNotContainsString($plain, $notification->toTelegram($claimant));
                $this->assertStringNotContainsString($plain, implode(' ', $notification->toMail($claimant)->introLines));

                return $notification->event === ActivityNotification::CODE_REISSUED
                    && $notification->url === route('claims.pickup', $claim);
            },
        );
    }

    #[Test]
    public function a_code_can_only_be_reissued_for_an_approved_claim(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem();
        $claim = Claim::factory()->create([
            'item_id' => $item->id,
            'status' => ClaimStatus::Submitted,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.claims.reissue', $claim))
            ->assertSessionHasErrors('reason');
    }

    #[Test]
    public function a_student_cannot_reissue_a_code_for_their_own_claim(): void
    {
        $item = $this->storedItem();
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->actingAs($claimant)
            ->post(route('admin.claims.reissue', $claim))
            ->assertForbidden();
    }

    #[Test]
    public function the_moderation_queue_surfaces_flagged_reports_first(): void
    {
        $admin = User::factory()->admin()->create();
        $this->storedItem(['title' => 'Laporan Biasa']);
        $flagged = $this->storedItem(['title' => 'Laporan Ditandai']);
        $flagged->forceFill(['flagged_at' => now(), 'flag_reason' => 'perlu dicek'])->save();

        $response = $this->actingAs($admin)->get(route('admin.items.index', ['only' => 'flagged']));

        $response->assertOk()->assertSee('Laporan Ditandai')->assertDontSee('Laporan Biasa');
    }

    #[Test]
    public function the_audit_log_records_moderation_with_previous_and_new_values(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem();

        $this->actingAs($admin)->post(route('admin.items.reject', $item), ['reason' => 'Palsu']);

        $log = AuditLog::query()->where('event', 'moderation.item_rejected')->firstOrFail();

        $this->assertSame(ItemStatus::Stored->value, $log->properties['previous_status']);
        $this->assertSame(ItemStatus::Rejected->value, $log->properties['new_status']);
        $this->assertSame($admin->id, $log->user_id);
    }

    #[Test]
    public function the_audit_log_never_contains_secrets(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->storedItem([
            'verification_answer' => Item::normalizeAnswer('stiker bulan sabit rahasia'),
        ]);
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant, 'stiker bulan sabit rahasia');
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();
        $code = $claim->pickupCode->plainCode();

        $this->actingAs($admin)->post(route('admin.claims.reissue', $claim));

        $dump = json_encode(AuditLog::query()->pluck('properties'));

        $this->assertStringNotContainsString('stiker bulan sabit rahasia', (string) $dump);
        $this->assertStringNotContainsString((string) $code, (string) $dump);
    }

    #[Test]
    public function an_admin_can_view_the_audit_log_and_user_list(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.audit.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.claims.index'))->assertOk();
    }

    #[Test]
    public function an_admin_can_deactivate_a_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.categories.toggle', $category))
            ->assertRedirect();

        $this->assertFalse($category->refresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['event' => 'moderation.category_toggled']);
    }
}
