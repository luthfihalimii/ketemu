<?php

namespace Tests\Feature\Items;

use App\Enums\ItemStatus;
use App\Exceptions\InvalidMatchException;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use App\Services\ItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

/**
 * Lost reports and found items are two different things. The link between them
 * records that they describe one belonging; it never grants the right to take
 * the item, which still requires the finder's verification answer.
 */
class LostFoundMatchTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    /**
     * A lost report as the student would have created it.
     */
    private function lostReport(User $owner, ?Category $category = null): Item
    {
        $item = new Item([
            'user_id' => $owner->id,
            'category_id' => ($category ?? Category::factory()->create())->id,
            'title' => 'Dompet Hilang',
            'description' => 'Dompet kulit hitam hilang di kantin.',
            'location_id' => Location::factory()->create()->id,
            'occurred_at' => now()->subDay(),
            'verification_question' => 'Ciri khusus barang ini?',
            'verification_answer' => Item::normalizeAnswer('jahitan merah di sudut'),
        ]);

        $item->status = ItemStatus::Reported;
        $item->save();

        return $item;
    }

    #[Test]
    public function a_lost_report_can_be_linked_to_a_found_item(): void
    {
        $owner = $this->student();
        $lost = $this->lostReport($owner);
        $found = $this->storedItem();

        $this->actingAs($owner)
            ->post(route('dashboard.matches.store', $lost), ['found_item_id' => $found->id])
            ->assertRedirect(route('dashboard.reports'));

        $this->assertTrue($lost->refresh()->isMatched());
        $this->assertSame($found->id, $lost->matched_item_id);
        $this->assertDatabaseHas('audit_logs', ['event' => 'item.matched']);
    }

    #[Test]
    public function the_link_can_be_undone_while_the_report_is_open(): void
    {
        $owner = $this->student();
        $lost = $this->lostReport($owner);
        $found = $this->storedItem();

        $this->actingAs($owner)->post(route('dashboard.matches.store', $lost), ['found_item_id' => $found->id]);

        $this->actingAs($owner)
            ->delete(route('dashboard.matches.destroy', $lost))
            ->assertRedirect();

        $this->assertFalse($lost->refresh()->isMatched());
        $this->assertDatabaseHas('audit_logs', ['event' => 'item.unmatched']);
    }

    #[Test]
    public function the_link_cannot_be_undone_once_the_owner_has_the_item_back(): void
    {
        $owner = $this->student();
        $lost = $this->lostReport($owner);
        $found = $this->storedItem();

        $lost->matchTo($found);
        $lost->setStatus(ItemStatus::Returned);
        $lost->save();

        $this->expectException(InvalidMatchException::class);

        app(ItemService::class)->unmatchLost($lost->refresh(), $owner);
    }

    #[Test]
    public function a_student_cannot_link_another_students_lost_report(): void
    {
        $owner = $this->student();
        $attacker = $this->student();
        $lost = $this->lostReport($owner);
        $found = $this->storedItem();

        $this->actingAs($attacker)
            ->post(route('dashboard.matches.store', $lost), ['found_item_id' => $found->id])
            ->assertForbidden();

        $this->assertFalse($lost->refresh()->isMatched());
    }

    #[Test]
    public function a_student_cannot_view_another_students_matches(): void
    {
        $owner = $this->student();
        $lost = $this->lostReport($owner);

        $this->actingAs($this->student())
            ->get(route('dashboard.matches', $lost))
            ->assertForbidden();
    }

    #[Test]
    public function the_match_page_is_not_available_for_a_found_item(): void
    {
        $found = $this->storedItem();

        $this->actingAs($found->user)
            ->get(route('dashboard.matches', $found))
            ->assertNotFound();
    }

    #[Test]
    public function a_found_item_cannot_be_matched_to_another_found_item(): void
    {
        $lost = $this->storedItem();
        $found = $this->storedItem();

        $this->expectException(InvalidMatchException::class);

        $lost->matchTo($found);
    }

    #[Test]
    public function an_item_cannot_be_matched_to_itself(): void
    {
        $lost = $this->lostReport($this->student());

        $this->expectException(InvalidMatchException::class);

        $lost->matchTo($lost);
    }

    #[Test]
    public function a_lost_report_cannot_be_published_as_a_found_item(): void
    {
        $owner = $this->student();
        $lost = $this->lostReport($owner);

        // Regression: this endpoint used to accept a lost report and pushed it
        // straight into the public catalogue.
        $this->actingAs($owner)
            ->post(route('items.confirm-deposit', $lost))
            ->assertForbidden();

        $this->assertSame(ItemStatus::Reported, $lost->refresh()->status);
        $this->assertFalse($lost->isPubliclyAvailable());
    }

    #[Test]
    public function only_found_items_are_offered_as_candidates(): void
    {
        $owner = $this->student();
        $category = Category::factory()->create();
        $lost = $this->lostReport($owner, $category);

        $match = $this->storedItem(['category_id' => $category->id, 'title' => 'Dompet Hitam']);
        $this->storedItem(['title' => 'Barang Kategori Lain']);
        $otherLost = $this->lostReport($this->student(), $category);

        $candidates = app(ItemService::class)->candidateFoundItems($lost);

        $this->assertTrue($candidates->contains($match));
        $this->assertFalse($candidates->contains($otherLost));
        $this->assertFalse($candidates->contains($lost));
    }

    #[Test]
    public function an_unclaimed_lost_report_is_not_publicly_visible(): void
    {
        $owner = $this->student();
        $lost = $this->lostReport($owner);

        // The catalogue must never show a report nobody is holding.
        $this->get(route('items.index'))->assertOk()->assertDontSee($lost->title);
    }

    #[Test]
    public function handing_over_a_found_item_closes_its_linked_lost_report(): void
    {
        $owner = $this->student();
        $lost = $this->lostReport($owner);
        $found = $this->storedItem();
        $claimant = $owner;

        $lost->matchTo($found);

        $code = $this->verifyClaim($found, $claimant);
        $this->redeem($code, User::factory()->guard()->create())->assertRedirect();

        $this->assertSame(ItemStatus::Returned, $found->refresh()->status);
        $this->assertSame(ItemStatus::Returned, $lost->refresh()->status);
    }
}
