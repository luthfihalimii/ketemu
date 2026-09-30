<?php

namespace Tests\Feature\Items;

use App\Enums\ItemStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class ItemSearchTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function it_lists_available_items_to_guests(): void
    {
        $item = $this->storedItem(['title' => 'Dompet Hitam']);

        $this->get(route('items.index'))
            ->assertOk()
            ->assertSee('Dompet Hitam')
            ->assertSee($item->category->name);
    }

    #[Test]
    public function it_hides_items_that_are_not_yet_deposited(): void
    {
        $this->storedItem(['title' => 'Barang Belum Dititipkan', 'status' => ItemStatus::WaitingDeposit]);
        $this->storedItem(['title' => 'Barang Sudah Dikembalikan', 'status' => ItemStatus::Returned]);

        $this->get(route('items.index'))
            ->assertOk()
            ->assertDontSee('Barang Belum Dititipkan')
            ->assertDontSee('Barang Sudah Dikembalikan');
    }

    #[Test]
    public function it_filters_by_keyword_across_title_description_and_brand(): void
    {
        $this->storedItem(['title' => 'Dompet Kulit', 'description' => 'warna gelap', 'brand' => 'Bonia']);
        $this->storedItem(['title' => 'Tumbler', 'description' => 'stainless', 'brand' => 'Lock']);

        $this->get(route('items.index', ['q' => 'Bonia']))->assertSee('Dompet Kulit')->assertDontSee('Tumbler');
        $this->get(route('items.index', ['q' => 'stainless']))->assertSee('Tumbler')->assertDontSee('Dompet Kulit');
        $this->get(route('items.index', ['q' => 'Dompet']))->assertSee('Dompet Kulit')->assertDontSee('Tumbler');
    }

    #[Test]
    public function it_filters_by_category(): void
    {
        $category = Category::factory()->create(['name' => 'Elektronik']);
        $other = Category::factory()->create(['name' => 'Pakaian']);

        $this->storedItem(['title' => 'Laptop Merah', 'category_id' => $category->id]);
        $this->storedItem(['title' => 'Jaket Biru', 'category_id' => $other->id]);

        $this->get(route('items.index', ['category' => $category->id]))
            ->assertSee('Laptop Merah')
            ->assertDontSee('Jaket Biru');
    }

    #[Test]
    public function it_filters_by_location(): void
    {
        $location = Location::factory()->create(['name' => 'Gedung D4']);
        $other = Location::factory()->create(['name' => 'Kantin']);

        $this->storedItem(['title' => 'Dompet D4', 'location_id' => $location->id]);
        $this->storedItem(['title' => 'Dompet Kantin', 'location_id' => $other->id]);

        $this->get(route('items.index', ['location' => $location->id]))
            ->assertSee('Dompet D4')
            ->assertDontSee('Dompet Kantin');
    }

    #[Test]
    public function it_filters_by_date(): void
    {
        $this->storedItem(['title' => 'Barang Kemarin', 'occurred_at' => now()->subDay()]);
        $this->storedItem(['title' => 'Barang Seminggu Lalu', 'occurred_at' => now()->subWeek()]);

        $this->get(route('items.index', ['date' => now()->subDay()->toDateString()]))
            ->assertSee('Barang Kemarin')
            ->assertDontSee('Barang Seminggu Lalu');
    }

    #[Test]
    public function it_ignores_a_status_filter_that_is_not_publicly_visible(): void
    {
        $this->storedItem(['title' => 'Barang Tersedia']);

        // A hand-crafted non-public status is ignored rather than leaking data.
        $this->get(route('items.index', ['status' => ItemStatus::WaitingDeposit->value]))
            ->assertOk()
            ->assertSee('Barang Tersedia');
    }

    #[Test]
    public function it_shows_an_empty_state_when_nothing_matches(): void
    {
        $this->storedItem(['title' => 'Dompet Hitam']);

        $this->get(route('items.index', ['q' => 'sepeda motor']))
            ->assertOk()
            ->assertSee('Barang belum ditemukan');
    }

    #[Test]
    public function it_rejects_invalid_filter_input(): void
    {
        $this->get(route('items.index', ['category' => 999999]))->assertSessionHasErrors('category');
        $this->get(route('items.index', ['date' => 'bukan-tanggal']))->assertSessionHasErrors('date');
    }

    #[Test]
    public function the_catalogue_never_leaks_verification_answers(): void
    {
        $this->storedItem([
            'title' => 'Dompet Rahasia',
            'verification_answer' => Item::normalizeAnswer('stiker bulan sabit di bagian dalam'),
        ]);

        $response = $this->get(route('items.index'));

        $response->assertOk()->assertDontSee('bulan sabit');
    }

    #[Test]
    public function pagination_keeps_the_active_filters(): void
    {
        Item::factory()->count(15)->create([
            'title' => 'Dompet Banyak',
            'category_id' => Category::factory()->create()->id,
        ]);

        $this->get(route('items.index', ['q' => 'Dompet']))
            ->assertOk()
            ->assertSee('q=Dompet', escape: false);
    }
}
