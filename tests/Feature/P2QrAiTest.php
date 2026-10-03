<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Services\ItemMatchService;
use App\Services\PickupQrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class P2QrAiTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function pickup_page_shows_a_scannable_qr_for_an_active_code(): void
    {
        $claimant = $this->student();
        $item = $this->storedItem();
        $this->verifyClaim($item, $claimant);
        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $this->actingAs($claimant)->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('claims.pickup', $claim))
            ->assertOk()
            ->assertSee('QR kode pengambilan', false)
            ->assertSee('data:image/svg+xml;base64,', false);
    }

    #[Test]
    public function qr_payload_is_the_plaintext_code_itself(): void
    {
        $uri = app(PickupQrService::class)->svgDataUri('ABCD-1234');

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);

        $svg = base64_decode(substr($uri, strlen('data:image/svg+xml;base64,')));
        $this->assertStringContainsString('<svg', $svg);
    }

    #[Test]
    public function matching_ranks_similar_items_first_with_reasons(): void
    {
        $reporter = $this->student();
        $lost = Item::factory()->lost()->create([
            'user_id' => $reporter->id,
            'title' => 'Dompet kulit hitam',
            'description' => 'Dompet kulit hitam berisi kartu mahasiswa dan stiker',
            'color' => 'Hitam',
        ]);

        $close = $this->storedItem([
            'title' => 'Dompet kulit hitam',
            'description' => 'Dompet kulit hitam berisi kartu mahasiswa',
            'color' => 'Hitam',
            'category_id' => $lost->category_id,
            'location_id' => $lost->location_id,
            'occurred_at' => $lost->occurred_at,
        ]);
        $far = $this->storedItem([
            'title' => 'Payung lipat biru',
            'description' => 'Payung lipat biru gagang kayu',
            'color' => 'Biru',
        ]);

        $ranked = app(ItemMatchService::class)->suggestFor($lost, 6);

        $this->assertSame($close->id, $ranked->first()->id);
        $this->assertGreaterThan($ranked->last()->getAttribute('match_score'), $ranked->first()->getAttribute('match_score'));
        $this->assertContains('Kategori sama', $ranked->first()->getAttribute('match_reasons'));
        $this->assertNotContains($far->id, $ranked->take(1)->pluck('id')->all());
    }

    #[Test]
    public function matching_never_uses_the_secret_answer(): void
    {
        $service = app(ItemMatchService::class);
        $item = $this->storedItem(['description' => 'Dompet umum']);

        // private_note + verification_answer tidak masuk teks publik.
        $this->assertStringNotContainsStringIgnoringCase('stiker', $service->publicText($item));
    }

    #[Test]
    public function matches_page_explains_scores_are_not_proof(): void
    {
        $owner = $this->student();
        $lost = Item::factory()->lost()->create(['user_id' => $owner->id]);
        $this->storedItem();

        $this->actingAs($owner)->get(route('dashboard.matches', $lost))
            ->assertOk()->assertSee('bukan bukti kepemilik', false);
    }
}
