<?php

namespace Tests\Unit;

use App\Enums\PickupCodeStatus;
use App\Models\Claim;
use App\Services\PickupCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PickupCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_issues_a_code_that_is_never_stored_in_plaintext(): void
    {
        $claim = Claim::factory()->create();
        $service = app(PickupCodeService::class);

        $result = $service->issue($claim);
        $plain = $result['plain'];
        $stored = $result['code']->getRawOriginal('code_hash');

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $plain);
        $this->assertNotSame($plain, $stored);
        $this->assertStringNotContainsString($plain, $stored);
    }

    #[Test]
    public function the_stored_hash_is_a_keyed_digest_not_a_bare_hash_of_the_code(): void
    {
        $claim = Claim::factory()->create();
        $result = app(PickupCodeService::class)->issue($claim);

        $this->assertNotSame(hash('sha256', $result['plain']), $result['code']->getRawOriginal('code_hash'));
    }

    #[Test]
    public function it_finds_an_active_code_from_user_input_ignoring_formatting(): void
    {
        $claim = Claim::factory()->create();
        $service = app(PickupCodeService::class);
        $plain = $service->issue($claim)['plain'];

        $messy = strtolower(str_replace('-', ' ', $plain));

        $found = $service->findActiveByPlainText($messy);

        $this->assertNotNull($found);
        $this->assertSame($claim->id, $found->claim_id);
    }

    #[Test]
    public function it_does_not_find_a_code_after_it_expires(): void
    {
        $claim = Claim::factory()->create();
        $service = app(PickupCodeService::class);
        $issued = $service->issue($claim);

        $issued['code']->update(['expires_at' => now()->subMinute()]);

        $this->assertNull($service->findActiveByPlainText($issued['plain']));
        $this->assertSame(PickupCodeStatus::Expired, $issued['code']->refresh()->effectiveStatus());
        $this->assertFalse($issued['code']->isUsable());
    }

    #[Test]
    public function it_does_not_find_a_code_that_was_already_used(): void
    {
        $claim = Claim::factory()->create();
        $service = app(PickupCodeService::class);
        $issued = $service->issue($claim);

        $issued['code']->update(['status' => PickupCodeStatus::Used, 'used_at' => now()]);

        $this->assertNull($service->findActiveByPlainText($issued['plain']));
    }

    #[Test]
    public function it_rejects_unknown_codes(): void
    {
        Claim::factory()->create();
        $service = app(PickupCodeService::class);

        $this->assertNull($service->findActiveByPlainText('ZZZZ-ZZZZ'));
        $this->assertNull($service->findActiveByPlainText(''));
    }

    #[Test]
    public function issuing_a_new_code_cancels_the_previous_active_one(): void
    {
        $claim = Claim::factory()->create();
        $service = app(PickupCodeService::class);

        $first = $service->issue($claim);
        $second = $service->issue($claim);

        $this->assertSame(PickupCodeStatus::Cancelled, $first['code']->refresh()->status);
        $this->assertSame(PickupCodeStatus::Active, $second['code']->status);
        $this->assertCount(2, $claim->pickupCodes);
    }

    #[Test]
    public function generated_codes_are_unguessable_and_unique(): void
    {
        $service = app(PickupCodeService::class);

        $codes = collect(range(1, 50))->map(fn () => $service->generatePlainCode());

        $this->assertCount(50, $codes->unique());
        $codes->each(fn (string $code) => $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code));
    }

    #[Test]
    public function the_code_is_recoverable_for_its_owner_through_encryption(): void
    {
        $claim = Claim::factory()->create();
        $plain = app(PickupCodeService::class)->issue($claim)['plain'];

        // Stored encrypted (not plaintext) yet readable by the owner's own page.
        $this->assertSame($plain, $claim->pickupCode()->first()->plainCode());
    }
}
