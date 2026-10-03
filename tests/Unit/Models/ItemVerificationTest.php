<?php

namespace Tests\Unit\Models;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ItemVerificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_accepts_the_correct_answer_regardless_of_case_and_whitespace(): void
    {
        $item = Item::factory()->create([
            'verification_answer' => Item::normalizeAnswer('  Stiker   BIRU di dalam '),
        ]);

        foreach (['stiker biru di dalam', 'STIKER BIRU DI DALAM', '  Stiker Biru Di Dalam  ', 'stiker  biru  di dalam'] as $attempt) {
            $this->assertTrue($item->verifyAnswer($attempt), "Expected \"{$attempt}\" to verify.");
        }
    }

    #[Test]
    public function it_rejects_wrong_answers(): void
    {
        $item = Item::factory()->create([
            'verification_answer' => Item::normalizeAnswer('stiker biru di dalam'),
        ]);

        foreach (['stiker merah', 'dompet hitam', 'biru', 'stiker biru di luarrrr'] as $attempt) {
            $this->assertFalse($item->verifyAnswer($attempt), "Expected \"{$attempt}\" to fail.");
        }
    }

    #[Test]
    public function it_rejects_blank_answers(): void
    {
        $item = Item::factory()->create([
            'verification_answer' => Item::normalizeAnswer('stiker biru di dalam'),
        ]);

        $this->assertFalse($item->verifyAnswer(null));
        $this->assertFalse($item->verifyAnswer(''));
        $this->assertFalse($item->verifyAnswer('   '));
    }

    #[Test]
    public function the_stored_answer_never_survives_as_plaintext(): void
    {
        $item = Item::factory()->create([
            'verification_answer' => Item::normalizeAnswer('stiker biru di dalam'),
        ]);

        $raw = $item->getRawOriginal('verification_answer');

        $this->assertNotSame('stiker biru di dalam', $raw);
        $this->assertTrue(password_verify('stiker biru di dalam', $raw));
    }

    #[Test]
    public function the_answer_is_hidden_from_serialisation(): void
    {
        $item = Item::factory()->create([
            'verification_answer' => Item::normalizeAnswer('stiker biru di dalam'),
        ]);

        $payload = $item->toArray();

        $this->assertArrayNotHasKey('verification_answer', $payload);
        $this->assertStringNotContainsString('stiker biru di dalam', json_encode($payload));
    }

    #[Test]
    public function it_generates_a_readable_unique_report_code(): void
    {
        $a = Item::factory()->create();
        $b = Item::factory()->create();

        $this->assertStringStartsWith('KP-', $a->code);
        $this->assertSame(39, strlen($a->code));
        $this->assertNotSame($a->code, $b->code);
    }

    #[Test]
    public function it_never_stores_the_verification_question_as_a_secret_channel(): void
    {
        $item = Item::factory()->create(['verification_question' => 'Apa warna stikernya?']);

        // The question is intentionally public: it guides the claim form.
        $this->assertSame('Apa warna stikernya?', $item->verification_question);
    }
}
