<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Attach defence-in-depth security headers to every web response.
 *
 * The CSP nonce is generated here and handed to Vite, so the bundle tags
 * emitted by @vite carry it. There is currently no inline <script> in the
 * Blade views; if one is ever added it must carry the same nonce.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(32);
        Vite::useCspNonce($nonce);

        $response = $next($request);

        $this->apply($request, $response, $nonce);

        return $response;
    }

    /**
     * Attach the headers to an already-built response.
     *
     * The exception handler reuses this for error pages, which never reach the
     * unwinding half of the middleware stack, so the nonce may be omitted there.
     */
    public function apply(Request $request, Response $response, ?string $nonce = null): void
    {
        // Never downgrade a header a controller or proxy already decided on.
        foreach ($this->headers($request, $nonce ?? Str::random(32)) as $header => $value) {
            if (! $response->headers->has($header)) {
                $response->headers->set($header, $value);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(Request $request, string $nonce): array
    {
        $headers = [
            // Baseline hardening, safe for every response.
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            // No feature on this site needs these device permissions.
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',

            'Content-Security-Policy' => $this->contentSecurityPolicy($nonce),
        ];

        // HSTS only makes sense over TLS; sending it on plain HTTP is both
        // useless and hostile to a local development setup.
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        return $headers;
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        $isLocal = app()->environment('local');

        // Vite's dev server serves modules from a websocket-backed origin and
        // injects styles at runtime, so the strict policy would break `npm run
        // dev`. Production keeps the tight policy: built assets are same-origin.
        $script = $isLocal
            ? "'self' 'nonce-{$nonce}' http://localhost:* http://127.0.0.1:*"
            : "'self' 'nonce-{$nonce}'";

        $style = "'self' 'nonce-{$nonce}' 'unsafe-inline'";
        $connect = $isLocal
            ? "'self' ws://localhost:* ws://127.0.0.1:* http://localhost:* http://127.0.0.1:*"
            : "'self'";

        return implode('; ', [
            "default-src 'self'",
            "script-src {$script}",
            "style-src {$style}",
            // Fonts are self-hosted, so no external font origin is required.
            "font-src 'self'",
            "img-src 'self' data:",
            "connect-src {$connect}",
            // Nothing here embeds third-party frames.
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
    }
}
