<?php

namespace Tests\Feature\Items;

use App\Enums\ItemStatus;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class ItemDetailTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function a_guest_can_open_the_detail_of_an_available_item(): void
    {
        $item = $this->storedItem([
            'title' => 'Dompet Hitam',
            'description' => 'Dompet kulit dengan resleting.',
        ]);

        $this->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('Dompet Hitam')
            ->assertSee($item->depositLocation->name)
            ->assertSee('Masuk untuk mengajukan klaim');
    }

    #[Test]
    public function the_detail_page_never_exposes_the_verification_answer(): void
    {
        $item = $this->storedItem([
            'verification_answer' => Item::normalizeAnswer('stiker bulan sabit di bagian dalam'),
        ]);

        $this->get(route('items.show', $item))
            ->assertOk()
            ->assertDontSee('bulan sabit')
            ->assertDontSee('stiker bulan sabit');
    }

    #[Test]
    public function the_detail_page_does_not_expose_the_reporters_personal_data(): void
    {
        $reporter = $this->student();
        $item = $this->storedItem([], $reporter);

        $this->get(route('items.show', $item))
            ->assertOk()
            ->assertDontSee($reporter->email);
    }

    #[Test]
    public function a_guest_cannot_open_an_item_that_is_not_available(): void
    {
        $item = $this->storedItem(['status' => ItemStatus::WaitingDeposit]);

        $this->get(route('items.show', $item))->assertForbidden();
    }

    #[Test]
    public function the_reporter_can_still_see_their_own_pending_item(): void
    {
        $reporter = $this->student();
        $item = $this->storedItem(['status' => ItemStatus::WaitingDeposit, 'stored_at' => null], $reporter);

        $this->actingAs($reporter)
            ->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('Tunjukkan QR ini ke satpam', false);
    }

    #[Test]
    public function a_guest_sees_a_login_call_to_action_instead_of_a_claim_button(): void
    {
        $item = $this->storedItem();

        $this->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('Masuk untuk mengajukan klaim');
    }

    #[Test]
    public function the_reporter_is_told_that_they_cannot_claim_their_own_item(): void
    {
        $reporter = $this->student();
        $item = $this->storedItem([], $reporter);

        $this->actingAs($reporter)
            ->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('Ini laporanmu');
    }
}
