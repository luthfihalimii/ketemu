<?php

use App\Exceptions\InvalidStatusTransitionException;
use App\Http\Middleware\EnsureNotBanned;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'not-banned' => EnsureNotBanned::class,
        ]);

        // Akun dinonaktifkan langsung ditolak di semua rute web terotentikasi.
        $middleware->appendToGroup('web', EnsureNotBanned::class);

        // Telegram POSTs the bot update directly and cannot carry a CSRF token;
        // the shared-secret header in TelegramWebhookController is its guard.
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
        ]);

        // Defence-in-depth headers on every web response, appended last so
        // it runs first and can also cover error responses.
        $middleware->appendToGroup('web', SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (InvalidStatusTransitionException $exception, Request $request) {
            $message = 'Status barang sudah berubah. Muat ulang halaman dan coba kembali.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 409);
            }

            return back()->withErrors(['status' => $message]);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Never expose a stack trace to a student on the web.
        $exceptions->dontFlash([
            'password',
            'password_confirmation',
            'answer',
            'verification_answer',
        ]);

        // Exception responses unwind past the middleware stack in a way that
        // skips the unwinding half of SecurityHeaders, so the headers are
        // applied here as well. Both paths skip headers already present.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            app(SecurityHeaders::class)->apply($request, $response);

            return $response;
        });
    })->create();
