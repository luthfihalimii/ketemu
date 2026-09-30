<?php

namespace Tests\Concerns;

use App\Enums\ItemStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use App\Services\PickupCodeService;
use Illuminate\Testing\TestResponse;

/**
 * Helpers for exercising the documented domain flow:
 * report -> deposit -> claim -> verify -> pickup -> returned.
 */
trait BuildsItemFlow
{
    protected function student(): User
    {
        return User::factory()->create();
    }

    /**
     * A stored, claimable item with a known verification answer.
     */
    protected function storedItem(array $attributes = [], ?User $reporter = null): Item
    {
        $reporter ??= User::factory()->create();

        return Item::factory()->create(array_merge([
            'user_id' => $reporter->id,
            'category_id' => Category::factory()->create()->id,
            'location_id' => Location::factory()->create()->id,
            'deposit_location_id' => Location::factory()->securityPost()->create()->id,
            'status' => ItemStatus::Stored,
            'verification_answer' => Item::normalizeAnswer('stiker biru di dalam'),
            'stored_at' => now(),
        ], $attributes));
    }

    protected function locations(): array
    {
        return [
            'category' => Category::factory()->create(),
            'location' => Location::factory()->create(),
            'post' => Location::factory()->securityPost()->create(),
        ];
    }

    /**
     * Drive the full verification flow and return the plaintext pickup code.
     */
    protected function verifyClaim(Item $item, User $claimant, string $answer = 'stiker biru di dalam'): string
    {
        $this->actingAs($claimant)
            ->post(route('claims.store', $item), ['answer' => $answer])
            ->assertRedirect();

        $claim = $item->claims()->where('user_id', $claimant->id)->firstOrFail();

        $code = $claim->pickupCode;

        $this->assertNotNull($code, "Expected a pickup code to be issued for \"{$answer}\".");

        return $code->plainCode() ?? '';
    }

    protected function pickupCodeService(): PickupCodeService
    {
        return app(PickupCodeService::class);
    }

    /**
     * Redeem a pickup code as a guard, always recording recipient identity.
     *
     * The identity fields are what a real handover requires, so tests go
     * through the same path rather than bypassing the form.
     */
    protected function redeem(
        string $plain,
        User $guard,
        string $idNumber = '2141720001',
        string $recipientName = 'Andi Penerima',
    ): TestResponse {
        return $this->actingAs($guard)->post(route('guard.pickup.store'), [
            'code' => $plain,
            'recipient_id_number' => $idNumber,
            'recipient_name' => $recipientName,
        ]);
    }
}
