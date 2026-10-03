<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\Role;
use App\Models\Item;
use App\Models\User;
use App\Services\ItemPhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class P0HardeningTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function item_urls_use_unguessable_codes_not_sequential_ids(): void
    {
        $item = $this->storedItem();

        $this->assertStringStartsWith('KP-', $item->code);

        $this->get(route('items.show', $item))->assertOk();
        $this->get('/items/'.$item->id)->assertNotFound();
    }

    #[Test]
    public function private_note_is_hidden_from_guests_but_visible_to_the_reporter(): void
    {
        $reporter = $this->student();
        $item = $this->storedItem(['private_note' => 'Goresan di sisi kiri untuk dikenali satpam.'], $reporter);

        $this->get(route('items.show', $item))
            ->assertOk()
            ->assertDontSee('Goresan di sisi kiri');

        $this->actingAs($reporter)->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('Goresan di sisi kiri');
    }

    #[Test]
    public function verification_answer_must_not_leak_into_public_fields(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $this->actingAs($student)->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'Dompet Kulit Hitam',
            'description' => 'Ada stiker bulan sabit di bagian dalam dompet ini',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'deposit_location_id' => $post->id,
            'verification_answer' => 'ada stiker bulan sabit di bagian dalam',
        ])->assertSessionHasErrors('verification_answer');

        $this->assertSame(0, Item::query()->count());
    }

    #[Test]
    public function owner_can_edit_a_report_before_deposit_but_not_after(): void
    {
        $reporter = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();
        $waiting = Item::factory()->create([
            'user_id' => $reporter->id,
            'category_id' => $category->id,
            'location_id' => $location->id,
            'deposit_location_id' => $post->id,
            'status' => ItemStatus::WaitingDeposit,
        ]);

        $this->actingAs($reporter)->get(route('items.edit', $waiting))->assertOk();
        $this->actingAs($reporter)->put(route('items.update', $waiting), [
            'category_id' => $category->id,
            'title' => 'Dompet Kulit Hitam Revisi',
            'description' => 'Revisi deskripsi umum.',
            'private_note' => 'Catatan internal revisi.',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'deposit_location_id' => $post->id,
        ])->assertRedirect(route('items.show', $waiting->refresh()));

        $this->assertSame('Dompet Kulit Hitam Revisi', $waiting->refresh()->title);

        $stored = $this->storedItem([], $reporter);
        $this->actingAs($reporter)->get(route('items.edit', $stored))->assertForbidden();
    }

    #[Test]
    public function guard_can_confirm_physical_deposit_as_second_party(): void
    {
        $reporter = $this->student();
        $guard = User::factory()->create(['role' => Role::Guard]);
        $item = Item::factory()->create([
            'user_id' => $reporter->id,
            'status' => ItemStatus::Stored,
            'deposit_requested_at' => now(),
        ]);

        $this->assertNull($item->deposit_confirmed_at);

        $this->actingAs($guard)->post(route('guard.deposit.confirm', $item))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertNotNull($item->refresh()->deposit_confirmed_at);
        $this->assertSame($guard->id, $item->refresh()->deposit_confirmed_by);
    }

    #[Test]
    public function missing_pages_use_indonesian_error_copy(): void
    {
        $this->get('/halaman-yang-tidak-ada-xyz')->assertNotFound();
    }

    #[Test]
    public function photo_urls_follow_the_configured_public_base_for_r2(): void
    {
        config([
            'ketemupens.photos.disk' => 'r2',
            'filesystems.disks.r2.url' => 'https://foto.ketemupens.test',
            'filesystems.disks.r2.bucket' => 'ketemupens-photos',
            'filesystems.disks.r2.endpoint' => 'https://account.r2.cloudflarestorage.com',
        ]);

        $url = app(ItemPhotoService::class)->url('items/abc.webp');

        $this->assertStringStartsWith('https://foto.ketemupens.test/', $url);
        $this->assertStringEndsWith('items/abc.webp', $url);
    }

    #[Test]
    public function health_command_passes_in_test_environment(): void
    {
        $this->artisan('ketemupens:health')->assertSuccessful();
    }
}
