<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class FrontendUxTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function archived_photos_are_visible_to_admins_only(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('moderation/items/photo.webp', 'private-photo');
        $item = $this->storedItem(['status' => ItemStatus::Rejected]);
        $item->archived_photo_path = 'items/photo.webp';
        $item->save();
        $this->actingAs($this->student())->get(route('admin.items.photo', $item))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.items.photo', $item))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('admin.items.show', $item))->assertOk()->assertSee(route('admin.items.photo', $item));
    }

    #[Test]
    public function mobile_navigation_keeps_notifications_and_settings_reachable(): void
    {
        $this->actingAs($this->student())->get(route('home'))->assertOk()
            ->assertSee('aria-label="Navigasi mobile"', false)
            ->assertSee('Pengaturan Telegram')->assertSee(route('notifications.index'));
    }

    #[Test]
    public function pickup_page_offers_copy_feedback_without_inline_handlers(): void
    {
        $item = $this->storedItem();
        $owner = $this->student();
        $plain = $this->verifyClaim($item, $owner);
        $claim = $item->claims()->firstOrFail();
        $this->actingAs($owner)->withConfirmedPassword()->get(route('claims.pickup', $claim))->assertOk()
            ->assertSee('Salin kode')->assertSee($plain)->assertDontSee('onclick=', false);
    }

    #[Test]
    public function validation_feedback_preserves_email_but_not_password(): void
    {
        $this->from(route('login'))->post(route('login'), ['email' => 'unknown@pens.ac.id', 'password' => 'secret-password'])->assertRedirect();
        $this->get(route('login'))->assertOk()->assertSee('Ada yang perlu diperbaiki.')
            ->assertSee('value="unknown@pens.ac.id"', false)->assertDontSee('value="secret-password"', false);
    }

    #[Test]
    public function a_verified_item_explains_why_it_cannot_be_claimed(): void
    {
        $item = $this->storedItem(['status' => ItemStatus::Verified]);
        $this->actingAs($this->student())->get(route('items.show', $item))->assertOk()
            ->assertSee('Barang ini sedang menunggu pengambilan oleh pemilik terverifikasi.');
    }

    #[Test]
    public function email_verification_logout_does_not_depend_on_inline_javascript(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get(route('verification.notice'))->assertOk()->assertDontSee('onclick=', false);
    }

    #[Test]
    public function rate_limit_errors_give_indonesian_recovery_instructions(): void
    {
        Route::get('/test-rate-limit', fn () => abort(429));
        $this->get('/test-rate-limit')->assertStatus(429)->assertSee('Terlalu banyak percobaan');
    }
}
