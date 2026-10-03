<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class P1FeatureTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function admin_can_ban_and_unban_a_user_who_is_then_blocked(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $student = $this->student();

        $this->actingAs($admin)->post(route('admin.users.ban', $student))->assertSessionHas('status');
        $this->assertNotNull($student->refresh()->banned_at);

        $this->actingAs($student)->get(route('dashboard'))->assertForbidden();

        $this->actingAs($admin)->post(route('admin.users.unban', $student))->assertSessionHas('status');
        $this->assertNull($student->refresh()->banned_at);
    }

    #[Test]
    public function admin_dashboard_and_exports_are_available(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->storedItem();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Dasbor Admin');
        $this->actingAs($admin)->get(route('admin.items.export'))->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    #[Test]
    public function api_catalog_is_public_by_code_without_secrets(): void
    {
        $item = $this->storedItem(['private_note' => 'Rahasia internal.']);

        $this->getJson(route('api.v1.items.index'))->assertOk()->assertJsonStructure(['data']);
        $this->getJson(route('api.v1.items.show', $item))->assertOk()
            ->assertJsonPath('data.code', $item->code)
            ->assertJsonMissing(['verification_answer' => true])
            ->assertJsonMissing(['private_note' => true]);

        $response = $this->getJson(route('api.v1.items.show', $item));
        $this->assertStringNotContainsString('Rahasia internal', $response->getContent());
    }

    #[Test]
    public function api_login_issues_a_token_and_me_works(): void
    {
        $user = User::factory()->create(['password' => 'secret1234']);

        $login = $this->postJson(route('api.v1.login'), [
            'email' => $user->email, 'password' => 'secret1234', 'device_name' => 'test',
        ])->assertOk();

        $token = $login->json('data.token');
        $this->assertNotEmpty($token);

        $this->withToken($token)->getJson(route('api.v1.me'))->assertOk()->assertJsonPath('data.email', $user->email);
    }

    #[Test]
    public function item_detail_includes_an_audit_timeline(): void
    {
        $reporter = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $this->actingAs($reporter)->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'Dompet Timeline',
            'description' => 'Deskripsi umum.',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'deposit_location_id' => $post->id,
            'verification_answer' => 'Ciri rahasia yang cukup panjang unik',
        ])->assertRedirect();

        $item = Item::query()->latest('id')->firstOrFail();

        $this->actingAs($reporter)->get(route('items.show', $item))
            ->assertOk()->assertSee('Riwayat proses');
    }

    #[Test]
    public function guard_receipt_shows_only_masked_identity(): void
    {
        $guard = User::factory()->create(['role' => Role::Guard]);
        $item = $this->storedItem();
        $code = $this->verifyClaim($item, $this->student());
        $pickup = $this->redeem($code, $guard);
        $pickup->assertSessionHasNoErrors();

        $pickupCode = $item->pickupCodes()->latest('id')->firstOrFail();

        $this->actingAs($guard)->get(route('guard.pickup.receipt', $pickupCode))
            ->assertOk()->assertSee('Struk Serah Terima')->assertSee('ID tersamar');
    }
}
