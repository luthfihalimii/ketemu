<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'rahasia-webhook';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.telegram.bot_token', '123:abc');
        config()->set('services.telegram.bot_username', 'ketemupens_bot');
        config()->set('services.telegram.webhook_secret', $this->secret);
    }

    #[Test]
    public function a_webhook_without_the_secret_is_rejected(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update('/start'))
            ->assertForbidden();

        $this->postJson(route('telegram.webhook'), $this->update('/start'), [
            'X-Telegram-Bot-Api-Secret-Token' => 'salah',
        ])->assertForbidden();
    }

    #[Test]
    public function a_valid_start_token_links_the_chat_and_cannot_be_replayed(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $telegram = app(TelegramService::class);
        $link = $telegram->issueLink($user);

        $token = str($link)->after('?start=')->toString();

        $this->postJson(route('telegram.webhook'), $this->update('/start '.$token), [
            'X-Telegram-Bot-Api-Secret-Token' => $this->secret,
        ])->assertNoContent();

        $this->assertEquals('999888', $user->refresh()->telegram_chat_id);

        // Token yang sama diputar ulang: sudah dibersihkan, harus gagal.
        $this->postJson(route('telegram.webhook'), $this->update('/start '.$token), [
            'X-Telegram-Bot-Api-Secret-Token' => $this->secret,
        ])->assertNoContent();

        $this->assertEquals('999888', $user->refresh()->telegram_chat_id);

        // Dua panggilan sendMessage: sukses + gagal-replay.
        Http::assertSentCount(2);
    }

    /** @return array<string, mixed> */
    private function update(string $text): array
    {
        return [
            'message' => [
                'text' => $text,
                'chat' => ['id' => 999888],
            ],
        ];
    }
}
