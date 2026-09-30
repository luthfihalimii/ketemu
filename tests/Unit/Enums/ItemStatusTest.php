<?php

namespace Tests\Unit\Enums;

use App\Enums\ItemStatus;
use App\Exceptions\InvalidStatusTransitionException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ItemStatusTest extends TestCase
{
    #[Test]
    public function it_follows_the_documented_forward_chain(): void
    {
        // The forward chain is the found-item flow: a found report is handed to
        // security, then claimed, verified, and collected.
        $chain = [
            ItemStatus::WaitingDeposit,
            ItemStatus::Stored,
            ItemStatus::Claimed,
            ItemStatus::Verified,
            ItemStatus::ReadyForPickup,
            ItemStatus::Returned,
        ];

        for ($i = 0; $i < count($chain) - 1; $i++) {
            $this->assertTrue(
                $chain[$i]->canTransitionTo($chain[$i + 1]),
                "Expected {$chain[$i]->value} -> {$chain[$i + 1]->value} to be allowed.",
            );
        }
    }

    #[Test]
    public function a_lost_report_can_never_enter_the_found_item_flow(): void
    {
        // REPORTED describes a lost report: nobody is holding the item, so it
        // must never reach the deposit or catalogue states. Allowing this once
        // let a student publish their own lost report as a found item.
        $this->assertFalse(ItemStatus::Reported->canTransitionTo(ItemStatus::WaitingDeposit));
        $this->assertFalse(ItemStatus::Reported->canTransitionTo(ItemStatus::Stored));

        // It resolves when the owner gets the belonging back.
        $this->assertTrue(ItemStatus::Reported->canTransitionTo(ItemStatus::Returned));
        $this->assertTrue(ItemStatus::Reported->canTransitionTo(ItemStatus::Rejected));
    }

    #[Test]
    public function it_never_allows_a_returned_item_back_to_reported(): void
    {
        $this->assertFalse(ItemStatus::Returned->canTransitionTo(ItemStatus::Reported));
        $this->assertSame([], ItemStatus::Returned->allowedTransitions());
    }

    #[Test]
    public function moderation_may_reject_a_report_before_handover(): void
    {
        $this->assertTrue(ItemStatus::Stored->canTransitionTo(ItemStatus::Rejected));
        $this->assertTrue(ItemStatus::ReadyForPickup->canTransitionTo(ItemStatus::Rejected));
        // Once handed over, a report is final and only an admin restore can move it.
        $this->assertFalse(ItemStatus::Returned->canTransitionTo(ItemStatus::Rejected));
    }

    #[Test]
    public function it_rejects_skipping_ahead_of_the_flow(): void
    {
        $this->assertFalse(ItemStatus::WaitingDeposit->canTransitionTo(ItemStatus::Verified));
        // An item cannot be collected before anyone has claimed it.
        $this->assertFalse(ItemStatus::Stored->canTransitionTo(ItemStatus::ReadyForPickup));
        $this->assertFalse(ItemStatus::Stored->canTransitionTo(ItemStatus::Returned));
    }

    #[Test]
    public function a_failed_or_cancelled_claim_releases_the_item_back_to_stored(): void
    {
        $this->assertTrue(ItemStatus::Claimed->canTransitionTo(ItemStatus::Stored));
        $this->assertTrue(ItemStatus::Verified->canTransitionTo(ItemStatus::Stored));
        $this->assertTrue(ItemStatus::ReadyForPickup->canTransitionTo(ItemStatus::Stored));
    }

    #[Test]
    public function transition_to_throws_on_an_invalid_move(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);

        ItemStatus::Returned->transitionTo(ItemStatus::Reported);
    }

    #[Test]
    public function transition_to_is_idempotent_for_the_same_status(): void
    {
        $this->assertSame(ItemStatus::Stored, ItemStatus::Stored->transitionTo(ItemStatus::Stored));
    }

    #[Test]
    public function only_stored_items_accept_claims(): void
    {
        $this->assertTrue(ItemStatus::Stored->acceptsClaims());

        foreach ([ItemStatus::Reported, ItemStatus::Claimed, ItemStatus::Verified, ItemStatus::Returned] as $status) {
            $this->assertFalse($status->acceptsClaims(), "{$status->value} should not accept claims.");
        }
    }

    #[Test]
    public function only_public_statuses_are_discoverable(): void
    {
        $this->assertTrue(ItemStatus::Stored->isPubliclyAvailable());
        $this->assertTrue(ItemStatus::ReadyForPickup->isPubliclyAvailable());
        $this->assertFalse(ItemStatus::Reported->isPubliclyAvailable());
        $this->assertFalse(ItemStatus::WaitingDeposit->isPubliclyAvailable());
    }

    #[Test]
    public function every_status_has_a_human_label(): void
    {
        foreach (ItemStatus::cases() as $status) {
            $this->assertNotSame('', $status->label());
            $this->assertNotSame($status->value, $status->label());
        }
    }
}
