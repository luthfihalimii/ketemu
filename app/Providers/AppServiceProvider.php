<?php

namespace App\Providers;

use App\Notifications\Channels\TelegramChannel;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        TrustProxies::at(config('ketemupens.trusted_proxies', []));
        $this->configureRateLimiting();
        $this->configureModels();
        $this->configureNotificationChannels();
    }

    /**
     * Register the Telegram notification channel under a plain name so the
     * notifiable can expose routeNotificationForTelegram().
     */
    private function configureNotificationChannels(): void
    {
        Notification::extend('telegram', fn ($app) => $app->make(TelegramChannel::class));
    }

    /**
     * Throttle abuse-prone endpoints: login, claim verification, and pickup
     * code redemption.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                Str::lower((string) $request->input('email')).'|'.$request->ip(),
            )->response(fn () => back()->withErrors([
                'email' => 'Terlalu banyak percobaan masuk. Silakan coba lagi nanti.',
            ]));
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Keyed by account so a shared campus network does not lock everyone out.
        RateLimiter::for('claims', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip(); // @phpstan-ignore nullsafe.neverNull (user() mengembalikan mixed; bisa null untuk guest)

            return Limit::perMinute(10)->by('claim|'.$key);
        });

        RateLimiter::for('pickup-verification', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip(); // @phpstan-ignore nullsafe.neverNull (user() mengembalikan mixed; bisa null untuk guest)

            // Keamanan berlapis dengan lockout bertingkat di PickupService:
            // throttle ini mencegah spam request, lockout di service mencegah
            // brute-force kode yang menyebar antar menit.
            return Limit::perMinute(15)->by('pickup|'.$key);
        });

        RateLimiter::for('reports', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip(); // @phpstan-ignore nullsafe.neverNull (user() mengembalikan mixed; bisa null untuk guest)

            return Limit::perMinute(10)->by('report|'.$key);
        });

        // Webhook Telegram dibatasi per IP dengan limiter bernama supaya
        // ambangnya terdokumentasi di satu tempat.
        RateLimiter::for('telegram-webhook', function (Request $request) {
            return Limit::perMinute(60)->by('telegram|'.$request->ip());
        });

        // Katalog publik rawan scraping enumerasi: batasi per IP agar crawler
        // agresif tertahan, pengguna normal tidak terdampak.
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(60)->by('search|'.$request->ip());
        });

        RateLimiter::for('api-search', function (Request $request) {
            return Limit::perMinute(60)->by('api-search|'.$request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip(); // @phpstan-ignore nullsafe.neverNull (user() mengembalikan mixed; bisa null untuk guest)

            return Limit::perMinute(60)->by('api|'.$key);
        });
    }

    private function configureModels(): void
    {
        // Surface accidental N+1 queries while developing.
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
