<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class HoldSlaTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function holding_requires_a_photo_and_a_promise(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $this->actingAs($student)->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'Dompet Tahan',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'deposit_location_id' => $post->id,
            'verification_answer' => 'Ciri rahasia yang cukup panjang',
        ])->assertSessionHasErrors(['photo', 'hold_promise']);

        $this->assertDatabaseCount('items', 0);
    }

    #[Test]
    public function holding_sets_a_deadline_while_direct_deposit_does_not(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $payload = [
            'category_id' => $category->id,
            'title' => 'Tas Tahan',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'deposit_location_id' => $post->id,
            'verification_answer' => 'Ciri rahasia yang cukup panjang',
            'photo' => UploadedFile::fake()->image('tas.jpg', 800, 600),
            'hold_promise' => '1',
        ];

        $this->actingAs($student)->post(route('items.store-found'), $payload)->assertRedirect();
        $held = Item::query()->latest('id')->firstOrFail();
        $this->assertSame(ItemStatus::WaitingDeposit, $held->status);
        $this->assertTrue($held->hold_promised);
        $this->assertNotNull($held->hold_until);
        $this->assertFalse($held->isHoldOverdue());

        $this->actingAs($student)->post(route('items.store-found'), $payload + [
            'title' => 'Tas Langsung Titip',
            'confirm_deposit' => '1',
        ])->assertRedirect();
        $direct = Item::query()->latest('id')->firstOrFail();
        $this->assertSame(ItemStatus::Stored, $direct->status);
        $this->assertNull($direct->hold_until);
    }

    #[Test]
    public function overdue_holds_are_flagged_once_with_notifications(): void
    {
        $reporter = $this->student();
        $item = Item::factory()->create([
            'user_id' => $reporter->id,
            'status' => ItemStatus::WaitingDeposit,
            'hold_until' => now()->subHour(),
            'hold_promised' => true,
        ]);

        $this->assertTrue($item->isHoldOverdue());

        $this->artisan('ketemupens:expire')->assertSuccessful();

        $this->assertNotNull($item->refresh()->deposit_reminded_at);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $reporter->id,
            'data->title' => 'Tenggat penitipan lewat',
        ]);
    }
}
