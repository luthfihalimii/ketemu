<?php

namespace Tests\Feature\Notifications;

use App\Enums\ItemStatus;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class TelegramNotificationTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.bot_username' => 'ketemupens_bot',
            'services.telegram.webhook_secret' => 'webhook-secret',
        ]);
    }

    #[Test]
    public function a_linked_student_gets_the_notification_on_telegram(): void
    {
        Http::fake();

        $item = $this->storedItem();
        $claimant = $this->student();
        $claimant->forceFill(['telegram_chat_id' => '555001'])->save();

        $this->verifyClaim($item, $claimant);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendMessage')
                && $request['chat_id'] === '555001'
                && str_contains($request['text'], 'Klaim disetujui');
        });
    }

    #[Test]
    public function an_unlinked_student_gets_no_telegram_call(): void
    {
        Http::fake();

        $item = $this->storedItem();
        $claimant = $this->student();

        $this->verifyClaim($item, $claimant);

        Http::assertNothingSent();
        // The in-app copy is still recorded.
        $this->assertSame(1, $claimant->notifications()->count());
    }

    #[Test]
    public function telegram_stays_off_when_the_bot_is_not_configured(): void
    {
        config(['services.telegram.bot_token' => null]);
        Http::fake();

        $item = $this->storedItem();
        $claimant = $this->student();
        $claimant->forceFill(['telegram_chat_id' => '555001'])->save();

        $this->verifyClaim($item, $claimant);

        Http::assertNothingSent();
        $this->assertSame(1, $claimant->notifications()->count());
    }

    #[Test]
    public function a_telegram_outage_never_breaks_the_action_that_triggered_it(): void
    {
        // The channel runs inside the claim transaction; a hard failure there
        // must not roll back a successful claim.
        Http::fake(fn () => Http::response(['ok' => false], 500));

        $item = $this->storedItem();
        $claimant = $this->student();
        $claimant->forceFill(['telegram_chat_id' => '555001'])->save();

        $code = $this->verifyClaim($item, $claimant);

        $this->assertNotEmpty($code);
        $this->assertSame(ItemStatus::ReadyForPickup, $item->refresh()->status);
    }

    #[Test]
    public function a_connection_error_is_swallowed_too(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $item = $this->storedItem();
        $claimant = $this->student();
        $claimant->forceFill(['telegram_chat_id' => '555001'])->save();

        $code = $this->verifyClaim($item, $claimant);

        $this->assertNotEmpty($code);
        $this->assertSame(ItemStatus::ReadyForPickup, $item->refresh()->status);
    }

    #[Test]
    public function an_unexpected_channel_failure_never_breaks_the_action(): void
    {
        // Guards the channel's own catch: a Throwable that is not an Exception
        // (TypeError, Error) must still not roll back the claim transaction.
        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturnTrue();
            $mock->shouldReceive('sendMessage')->andThrow(new \TypeError('boom'));
        });

        $item = $this->storedItem();
        $claimant = $this->student();
        $claimant->forceFill(['telegram_chat_id' => '555001'])->save();

        $code = $this->verifyClaim($item, $claimant);

        $this->assertNotEmpty($code);
        $this->assertSame(ItemStatus::ReadyForPickup, $item->refresh()->status);
    }

    #[Test]
    public function the_settings_page_issues_a_deep_link(): void
    {
        $user = $this->student();

        $this->actingAs($user)
            ->get(route('telegram.show'))
            ->assertOk()
            ->assertSee('https://t.me/ketemupens_bot?start=', escape: false);

        $this->assertNotNull($user->refresh()->telegram_link_token);
    }

    #[Test]
    public function the_settings_page_says_so_when_telegram_is_not_configured(): void
    {
        config(['services.telegram.bot_token' => null]);

        $this->actingAs($this->student())
            ->get(route('telegram.show'))
            ->assertOk()
            ->assertSee('Telegram belum aktif');
    }

    #[Test]
    public function starting_the_bot_with_a_valid_token_links_the_account(): void
    {
        Http::fake();

        $user = $this->student();
        $telegram = app(TelegramService::class);
        $link = $telegram->issueLink($user);

        $token = Str::after($link, 'start=');

        $this->postJson(route('telegram.webhook'), [
            'message' => ['text' => '/start '.$token, 'chat' => ['id' => 998877]],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'webhook-secret'])->assertNoContent();

        $user->refresh();

        $this->assertSame('998877', $user->telegram_chat_id);
        $this->assertNotNull($user->telegram_linked_at);
        // The token is consumed, so it cannot be replayed.
        $this->assertNull($user->telegram_link_token);
    }

    #[Test]
    public function an_expired_token_does_not_link(): void
    {
        Http::fake();

        $user = $this->student();
        $link = app(TelegramService::class)->issueLink($user);
        $token = Str::after($link, 'start=');

        $user->forceFill(['telegram_link_token_expires_at' => now()->subMinute()])->save();

        $this->postJson(route('telegram.webhook'), [
            'message' => ['text' => '/start '.$token, 'chat' => ['id' => 998877]],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'webhook-secret'])->assertNoContent();

        $this->assertNull($user->refresh()->telegram_chat_id);
    }

    #[Test]
    public function a_replayed_token_cannot_link_a_second_account(): void
    {
        Http::fake();

        $user = $this->student();
        $token = Str::after(app(TelegramService::class)->issueLink($user), 'start=');

        $headers = ['X-Telegram-Bot-Api-Secret-Token' => 'webhook-secret'];

        $this->postJson(route('telegram.webhook'), [
            'message' => ['text' => '/start '.$token, 'chat' => ['id' => 111]],
        ], $headers)->assertNoContent();

        // Same token, different chat: must not steal the link.
        $this->postJson(route('telegram.webhook'), [
            'message' => ['text' => '/start '.$token, 'chat' => ['id' => 222]],
        ], $headers)->assertNoContent();

        $this->assertSame('111', $user->refresh()->telegram_chat_id);
        $this->assertSame(0, User::query()->where('telegram_chat_id', '222')->count());
    }

    #[Test]
    public function the_webhook_rejects_a_request_without_the_secret(): void
    {
        Http::fake();

        $user = $this->student();
        $token = Str::after(app(TelegramService::class)->issueLink($user), 'start=');

        $this->postJson(route('telegram.webhook'), [
            'message' => ['text' => '/start '.$token, 'chat' => ['id' => 998877]],
        ])->assertForbidden();

        $this->assertNull($user->refresh()->telegram_chat_id);
    }

    #[Test]
    public function the_webhook_rejects_a_request_with_the_wrong_secret(): void
    {
        Http::fake();

        $this->postJson(route('telegram.webhook'), [
            'message' => ['text' => '/start whatever', 'chat' => ['id' => 1]],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'wrong'])->assertForbidden();
    }

    #[Test]
    public function the_webhook_ignores_unrelated_messages(): void
    {
        Http::fake();

        $this->postJson(route('telegram.webhook'), [
            'message' => ['text' => 'halo bot', 'chat' => ['id' => 1]],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'webhook-secret'])->assertNoContent();

        Http::assertNothingSent();
    }

    #[Test]
    public function a_student_can_unlink_telegram(): void
    {
        $user = $this->student();
        $user->forceFill([
            'telegram_chat_id' => '555001',
            'telegram_linked_at' => now(),
        ])->save();

        $this->actingAs($user)
            ->delete(route('telegram.unlink'))
            ->assertRedirect();

        $user->refresh();

        $this->assertNull($user->telegram_chat_id);
        $this->assertFalse($user->hasLinkedTelegram());
    }

    #[Test]
    public function the_webhook_is_not_available_to_guests_of_the_session(): void
    {
        // Regression guard: the route must stay outside the auth middleware,
        // because Telegram has no session. It is guarded by the secret instead.
        Http::fake();

        $this->postJson(route('telegram.webhook'), ['message' => ['text' => '/start x', 'chat' => ['id' => 1]]],
            ['X-Telegram-Bot-Api-Secret-Token' => 'webhook-secret']
        )->assertNoContent();
    }
}
