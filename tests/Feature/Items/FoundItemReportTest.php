<?php

namespace Tests\Feature\Items;

use App\Enums\ItemStatus;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class FoundItemReportTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function a_student_can_report_a_found_item(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $response = $this->actingAs($student)->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'Dompet Kulit Hitam',
            'description' => 'Ditemukan di kursi lantai 2, ada beberapa kartu di dalamnya.',
            'color' => 'Hitam',
            'brand' => 'Bonia',
            'location_id' => $location->id,
            'location_detail' => 'Dekat lift',
            'occurred_at' => now()->subHours(2)->toDateTimeString(),
            'deposit_location_id' => $post->id,
            'verification_question' => 'Apa yang ada di dalam dompet?',
            'verification_answer' => 'Ada stiker bulan sabit di bagian dalam',
        ]);

        $item = Item::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('items.show', $item));
        $this->assertSame($student->id, $item->user_id);
        $this->assertSame('Dompet Kulit Hitam', $item->title);
        $this->assertTrue($item->verifyAnswer('ada stiker bulan sabit di bagian dalam'));
    }

    #[Test]
    public function a_new_found_report_waits_for_deposit_before_it_can_be_claimed(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $this->actingAs($student)->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'Earphone Putih',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'deposit_location_id' => $post->id,
            'verification_answer' => 'Ada nama tertulis di kotaknya',
        ]);

        $item = Item::query()->latest('id')->firstOrFail();

        $this->assertSame(ItemStatus::WaitingDeposit, $item->status);
        $this->assertFalse($item->status->isPubliclyAvailable());
        $this->assertFalse($item->status->acceptsClaims());
    }

    #[Test]
    public function the_finder_can_confirm_the_deposit_and_make_it_claimable(): void
    {
        $student = $this->student();
        $item = Item::factory()->create([
            'user_id' => $student->id,
            'status' => ItemStatus::WaitingDeposit,
            'stored_at' => null,
        ]);

        $this->actingAs($student)
            ->post(route('items.confirm-deposit', $item))
            ->assertRedirect();

        $item->refresh();

        $this->assertSame(ItemStatus::Stored, $item->status);
        $this->assertNotNull($item->stored_at);
        $this->assertTrue($item->status->isPubliclyAvailable());
    }

    #[Test]
    public function another_student_cannot_confirm_someone_elses_deposit(): void
    {
        $item = $this->storedItem(['status' => ItemStatus::WaitingDeposit, 'stored_at' => null]);

        $this->actingAs($this->student())
            ->post(route('items.confirm-deposit', $item))
            ->assertForbidden();

        $this->assertSame(ItemStatus::WaitingDeposit, $item->refresh()->status);
    }

    #[Test]
    public function the_report_requires_a_deposit_location_that_is_a_security_post(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location] = $this->locations();

        $this->actingAs($student)->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'Jaket Biru',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            // A plain building is not a valid place to hand an item over.
            'deposit_location_id' => $location->id,
            'verification_answer' => 'Ada bordir nama di bagian belakang',
        ])->assertSessionHasErrors('deposit_location_id');

        $this->assertDatabaseCount('items', 0);
    }

    #[Test]
    public function the_report_requires_an_ownership_verification_answer(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $this->actingAs($student)->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'Botol Minum',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'deposit_location_id' => $post->id,
        ])->assertSessionHasErrors('verification_answer');
    }

    #[Test]
    public function the_discovery_time_cannot_be_in_the_future(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $this->actingAs($student)->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'Kunci Motor',
            'location_id' => $location->id,
            'occurred_at' => now()->addDay()->toDateTimeString(),
            'deposit_location_id' => $post->id,
            'verification_answer' => 'Ada gantungan kunci berbentuk bola',
        ])->assertSessionHasErrors('occurred_at');
    }

    #[Test]
    public function a_student_can_report_a_found_item_and_confirm_deposit_in_one_step(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location, 'post' => $post] = $this->locations();

        $this->actingAs($student)->post(route('items.store-found'), [
            'category_id' => $category->id,
            'title' => 'Flashdisk Merah',
            'location_id' => $location->id,
            'occurred_at' => now()->subHour()->toDateTimeString(),
            'deposit_location_id' => $post->id,
            'verification_answer' => 'Isi folder diberi nama proyek akhir',
            'confirm_deposit' => '1',
        ]);

        $this->assertSame(ItemStatus::Stored, Item::query()->latest('id')->firstOrFail()->status);
    }
}
