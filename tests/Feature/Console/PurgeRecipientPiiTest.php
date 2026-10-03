<?php

namespace Tests\Feature\Console;

use App\Models\PickupCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class PurgeRecipientPiiTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function purge_clears_recipient_pii_older_than_the_retention_window(): void
    {
        $old = PickupCode::factory()->create([
            'used_at' => now()->subDays(91),
            'recipient_id_number' => '3201234567890001',
            'recipient_name' => 'Budi Lama',
        ]);

        $recent = PickupCode::factory()->create([
            'used_at' => now()->subDays(10),
            'recipient_id_number' => '3201234567890002',
            'recipient_name' => 'Andi Baru',
        ]);

        $this->artisan('ketemupens:purge-pii')->assertSuccessful();

        $this->assertNull($old->refresh()->recipient_id_number);
        $this->assertNull($old->recipient_name);

        $this->assertSame('3201234567890002', $recent->refresh()->recipient_id_number);
        $this->assertSame('Andi Baru', $recent->recipient_name);
    }

    #[Test]
    public function purge_never_touches_codes_that_have_not_been_used(): void
    {
        $code = PickupCode::factory()->create([
            'used_at' => null,
            'recipient_id_number' => '3201234567890001',
            'recipient_name' => 'Budi Lama',
        ]);

        $this->artisan('ketemupens:purge-pii')->assertSuccessful();

        $this->assertSame('3201234567890001', $code->refresh()->recipient_id_number);
    }

    #[Test]
    public function recipient_columns_are_encrypted_at_rest(): void
    {
        $code = PickupCode::factory()->create([
            'recipient_id_number' => '3201234567890001',
            'recipient_name' => 'Budi Terenkripsi',
        ]);

        $raw = $code->newQuery()->whereKey($code->id)->firstOrFail()->getRawOriginal();

        $this->assertStringNotContainsString('3201234567890001', (string) $raw['recipient_id_number']);
        $this->assertStringNotContainsString('Budi Terenkripsi', (string) $raw['recipient_name']);

        // Model tetap mengembalikan plaintext.
        $this->assertSame('3201234567890001', $code->refresh()->recipient_id_number);
        $this->assertSame('Budi Terenkripsi', $code->recipient_name);
    }
}
