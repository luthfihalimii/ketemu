<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\ItemStatus;
use App\Enums\PickupCodeStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Item;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ItemPhotoService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class BackendRegressionTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function mass_assignment_cannot_change_roles_or_moderation_state(): void
    {
        $user = $this->student();
        $user->fill(['role' => 'admin'])->save();
        $this->assertFalse($user->refresh()->isAdmin());
        $item = $this->storedItem();
        $item->fill(['status' => ItemStatus::Returned, 'flagged_at' => now()])->save();
        $this->assertSame(ItemStatus::Stored, $item->refresh()->status);
        $this->assertNull($item->flagged_at);
    }

    #[Test]
    public function invalid_status_transitions_return_a_safe_conflict_response(): void
    {
        Route::get('/test-status-conflict', function (): void {
            throw InvalidStatusTransitionException::make(ItemStatus::Returned, ItemStatus::Reported);
        });
        $this->getJson('/test-status-conflict')->assertStatus(409)
            ->assertJson(['success' => false, 'message' => 'Status barang sudah berubah. Muat ulang halaman dan coba kembali.']);
    }

    #[Test]
    public function forwarded_ips_are_only_accepted_from_configured_proxies(): void
    {
        TrustProxies::at(['10.0.0.1']);
        Route::get('/test-client-ip', fn () => response()->json(['ip' => request()->ip()]));

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeader('X-Forwarded-For', '203.0.113.5')->getJson('/test-client-ip')
            ->assertJson(['ip' => '203.0.113.5']);
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.1'])
            ->getJson('/test-client-ip')->assertJson(['ip' => '198.51.100.1']);
    }

    #[Test]
    public function guards_cannot_read_a_claimants_pickup_code(): void
    {
        $item = $this->storedItem();
        $this->verifyClaim($item, $this->student());
        $claim = $item->claims()->firstOrFail();
        $this->actingAs(User::factory()->guard()->create())->withConfirmedPassword()
            ->get(route('claims.pickup', $claim))->assertForbidden();
    }

    #[Test]
    public function an_inactive_duplicate_hash_does_not_hide_an_active_pickup_code(): void
    {
        $item = $this->storedItem();
        $plain = $this->verifyClaim($item, $this->student());
        $old = $item->pickupCodes()->firstOrFail();
        $new = $old->replicate();
        $new->save();
        $old->status = PickupCodeStatus::Cancelled;
        $old->save();

        $this->assertSame($new->id, $this->pickupCodeService()->findActiveByPlainText($plain)?->id);
    }

    #[Test]
    public function password_reset_rejects_invalid_tokens_and_weak_passwords(): void
    {
        $user = $this->student();
        $original = $user->password;
        $this->post('/reset-password', ['email' => $user->email, 'token' => 'invalid', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])
            ->assertSessionHasErrors('email');
        $this->post('/reset-password', ['email' => $user->email, 'token' => Password::createToken($user), 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
        $this->assertSame($original, $user->refresh()->password);
    }

    #[Test]
    public function password_recovery_forms_are_accessible_from_login(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('password.request'));
        $this->get(route('password.request'))->assertOk();
        $this->get(route('password.reset', ['token' => 'sample', 'email' => 'user@pens.ac.id']))->assertOk();
    }

    #[Test]
    public function whitespace_cannot_pad_a_short_verification_answer(): void
    {
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();
        $this->actingAs($this->student())->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'Dompet Hitam',
            'location_id' => $location->id,
            'deposit_location_id' => $post->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'verification_answer' => '       abc       ',
        ])->assertSessionHasErrors('verification_answer');
        $this->assertDatabaseCount('items', 0);
    }

    #[Test]
    public function rejected_claims_cannot_reset_the_verification_attempt_budget(): void
    {
        $user = $this->student();
        $item = $this->storedItem();
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)->post(route('claims.store', $item), ['answer' => 'jawaban salah']);
        }
        $this->post(route('claims.store', $item), ['answer' => 'stiker biru di dalam'])->assertSessionHasErrors('answer');
        $this->assertSame(3, $item->claims()->firstOrFail()->attempt_count);
        $this->assertDatabaseCount('pickup_codes', 0);
    }

    #[Test]
    public function a_cancelled_claim_can_be_retried_without_resetting_attempts(): void
    {
        $owner = $this->student();
        $item = $this->storedItem();
        $this->verifyClaim($item, $owner);
        $claim = $item->claims()->firstOrFail();
        $this->actingAs($owner)->delete(route('claims.cancel', $claim))->assertRedirect();

        $this->actingAs($owner)->post(route('claims.store', $item), ['answer' => 'stiker biru di dalam'])
            ->assertSessionHasNoErrors();

        $this->assertSame(ClaimStatus::Approved, $claim->refresh()->status);
        $this->assertSame(2, $claim->attempt_count);
    }

    #[Test]
    public function a_lost_report_does_not_require_or_store_an_unused_secret(): void
    {
        $this->actingAs($this->student())->post(route('items.store-lost'), $this->lostPayload())
            ->assertSessionHasNoErrors();

        $item = Item::query()->firstOrFail();
        $this->assertNull($item->verification_answer);
        $this->assertNull($item->verification_question);
    }

    #[Test]
    #[TestWith(['REJECTED'])]
    #[TestWith(['EXPIRED'])]
    public function deposit_confirmation_cannot_restore_a_removed_report(string $status): void
    {
        $owner = $this->student();
        $item = $this->storedItem(['status' => ItemStatus::from($status)], $owner);

        $this->actingAs($owner)->post(route('items.confirm-deposit', $item))->assertForbidden();
        $this->assertSame(ItemStatus::from($status), $item->refresh()->status);
    }

    #[Test]
    public function a_rejected_photo_is_private_and_survives_restoration(): void
    {
        Storage::fake(ItemPhotoService::disk());
        Storage::fake('local');
        Storage::disk(ItemPhotoService::disk())->put('items/photo.webp', 'photo');
        $item = $this->storedItem(['photo_path' => 'items/photo.webp']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.items.reject', $item), ['reason' => 'Periksa laporan'])
            ->assertSessionHasNoErrors();
        Storage::disk('local')->assertExists('moderation/items/photo.webp');
        $this->assertNull($item->refresh()->photo_path);

        $this->post(route('admin.items.restore', $item))->assertSessionHasNoErrors();
        $this->assertSame('items/photo.webp', $item->refresh()->photo_path);
        $this->assertSame('photo', Storage::disk(ItemPhotoService::disk())->get('items/photo.webp'));
    }

    #[Test]
    public function a_hidden_found_report_cannot_be_linked(): void
    {
        $owner = $this->student();
        $lost = Item::factory()->lost()->create(['user_id' => $owner->id]);
        $found = $this->storedItem(['status' => ItemStatus::WaitingDeposit]);

        $this->actingAs($owner)->post(route('dashboard.matches.store', $lost), ['found_item_id' => $found->id])
            ->assertForbidden();
        $this->assertNull($lost->refresh()->matched_item_id);
    }

    #[Test]
    public function pickup_does_not_close_another_students_linked_lost_report(): void
    {
        $lost = Item::factory()->lost()->create();
        $found = $this->storedItem();
        $lost->matchTo($found);
        $code = $this->verifyClaim($found, $this->student());

        $this->redeem($code, User::factory()->guard()->create())->assertSessionHasNoErrors();
        $this->assertSame(ItemStatus::Reported, $lost->refresh()->status);
    }

    #[Test]
    public function an_image_processing_failure_does_not_leave_a_report_or_original_upload(): void
    {
        Storage::fake(ItemPhotoService::disk());
        $this->mock(ItemPhotoService::class, function ($mock): void {
            $mock->shouldReceive('store')->andThrow(ValidationException::withMessages(['photo' => 'Foto tidak dapat diproses.']));
            $mock->shouldReceive('delete')->with(null);
        });

        $this->actingAs($this->student())->post(route('items.store-lost'), $this->lostPayload() + [
            'verification_answer' => 'unused secret',
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('items', 0);
        $this->assertSame([], Storage::disk(ItemPhotoService::disk())->allFiles());
    }

    #[Test]
    public function an_audit_failure_rolls_back_the_report_and_removes_its_photo(): void
    {
        Storage::fake(ItemPhotoService::disk());
        $this->mock(AuditLogger::class)->shouldReceive('log')->andThrow(new \RuntimeException('Audit unavailable'));

        $this->actingAs($this->student())->post(route('items.store-lost'), $this->lostPayload() + [
            'verification_answer' => 'unused secret',
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertStatus(500);

        $this->assertDatabaseCount('items', 0);
        $this->assertSame([], Storage::disk(ItemPhotoService::disk())->allFiles());
    }

    #[Test]
    public function oversized_image_dimensions_are_rejected_before_processing(): void
    {
        $this->actingAs($this->student())->post(route('items.store-lost'), $this->lostPayload() + [
            'verification_answer' => 'unused secret',
            'photo' => UploadedFile::fake()->image('wide.jpg', 5000, 1),
        ])->assertSessionHasErrors('photo');
        $this->assertDatabaseCount('items', 0);
    }

    #[Test]
    public function search_treats_wildcards_as_literal_characters(): void
    {
        $this->storedItem(['title' => 'Dompet Hitam']);
        $literal = $this->storedItem(['title' => 'Label 100%']);

        $this->get(route('items.index', ['q' => '%']))->assertOk()
            ->assertSee($literal->title)->assertDontSee('Dompet Hitam');
    }

    #[Test]
    public function password_reset_links_do_not_reveal_whether_an_email_exists(): void
    {
        Notification::fake();
        $user = $this->student();

        $this->post('/lupa-password', ['email' => $user->email])->assertRedirect()->assertSessionHasNoErrors();
        $knownStatus = session('status');
        $this->post('/lupa-password', ['email' => 'unknown@pens.ac.id'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($knownStatus, session('status'));
        Notification::assertSentTo($user, ResetPassword::class);
    }

    #[Test]
    public function password_reset_tokens_are_single_use_and_rotate_remember_tokens(): void
    {
        $user = $this->student();
        $user->forceFill(['remember_token' => 'old-token'])->save();
        $token = Password::createToken($user);
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'];

        $this->post('/reset-password', $payload)->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPassword123', $user->refresh()->password));
        $this->assertNotSame('old-token', $user->remember_token);
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
    }

    /** @return array<string, mixed> */
    private function lostPayload(): array
    {
        ['category' => $category, 'location' => $location] = $this->locations();

        return ['category_id' => $category->id, 'title' => 'Dompet Hilang', 'description' => 'Dompet hilang di gedung D4.', 'location_id' => $location->id, 'occurred_at' => now()->subHour()->toDateTimeString()];
    }
}
