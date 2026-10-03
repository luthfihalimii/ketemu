<?php

namespace Tests\Feature\Items;

use App\Enums\ItemStatus;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class LostItemReportTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function a_student_can_report_a_lost_item(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location] = $this->locations();

        $response = $this->actingAs($student)->post(route('items.store-lost'), [
            'category_id' => $category->id,
            'title' => 'Tumbler Biru',
            'description' => 'Tumbler stainless 500ml dengan tulisan nama di bagian bawah.',
            'color' => 'Biru',
            'location_id' => $location->id,
            'occurred_at' => now()->subDay()->toDateTimeString(),
            'verification_answer' => 'Ada goresan inisial A di bagian bawah',
        ]);

        $item = Item::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('items.show', $item));
        $this->assertSame(ItemStatus::Reported, $item->status);
        $this->assertNull($item->verification_answer);
    }

    #[Test]
    public function a_lost_report_is_never_shown_in_the_public_catalogue(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location] = $this->locations();

        $this->actingAs($student)->post(route('items.store-lost'), [
            'category_id' => $category->id,
            'title' => 'Dompet Coklat Rahasia',
            'description' => 'Hilang saat praktikum di laboratorium jaringan.',
            'location_id' => $location->id,
            'occurred_at' => now()->subDay()->toDateTimeString(),
            'verification_answer' => 'Ada foto keluarga di balik kartu',
        ]);

        $item = Item::query()->latest('id')->firstOrFail();

        $this->get(route('items.index'))
            ->assertOk()
            ->assertDontSee('Dompet Coklat Rahasia');

        $this->assertFalse($item->isPubliclyAvailable());
    }

    #[Test]
    public function a_lost_report_cannot_be_claimed(): void
    {
        $item = $this->storedItem(['status' => ItemStatus::Reported]);

        $this->actingAs($this->student())
            ->get(route('claims.create', $item))
            ->assertForbidden();
    }

    #[Test]
    public function the_lost_report_requires_a_description(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location] = $this->locations();

        $this->actingAs($student)->post(route('items.store-lost'), [
            'category_id' => $category->id,
            'title' => 'Helm',
            'location_id' => $location->id,
            'occurred_at' => now()->subDay()->toDateTimeString(),
        ])->assertSessionHasErrors(['description']);
    }

    #[Test]
    public function unused_ciri_khusus_is_not_stored(): void
    {
        $student = $this->student();
        ['category' => $category, 'location' => $location] = $this->locations();

        $this->actingAs($student)->post(route('items.store-lost'), [
            'category_id' => $category->id,
            'title' => 'Kunci Motor',
            'description' => 'Hilang di parkiran gedung D4 setelah kuliah pagi.',
            'location_id' => $location->id,
            'occurred_at' => now()->subDay()->toDateTimeString(),
            'verification_answer' => 'Gantungan kunci berbentuk bola basket',
        ]);

        $raw = Item::query()->latest('id')->firstOrFail()->getRawOriginal('verification_answer');

        $this->assertNull($raw);
    }
}
