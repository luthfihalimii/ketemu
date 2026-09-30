<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_home_page_carries_the_baseline_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $this->assertStringContainsString(
            'geolocation=()',
            (string) $response->headers->get('Permissions-Policy'),
        );
    }

    #[Test]
    public function the_content_security_policy_locks_down_the_default_sources(): void
    {
        $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        // Fonts are self-hosted, so no external font origin may be allowed.
        $this->assertStringContainsString("font-src 'self'", $csp);
    }

    #[Test]
    public function the_csp_uses_a_fresh_nonce_per_request(): void
    {
        $first = (string) $this->get('/')->headers->get('Content-Security-Policy');
        $second = (string) $this->get('/')->headers->get('Content-Security-Policy');

        preg_match("/'nonce-([^']+)'/", $first, $a);
        preg_match("/'nonce-([^']+)'/", $second, $b);

        $this->assertNotEmpty($a[1] ?? null);
        $this->assertNotEmpty($b[1] ?? null);
        $this->assertNotSame($a[1], $b[1], 'Each response must get its own nonce.');
    }

    #[Test]
    public function the_vite_bundle_tags_carry_the_csp_nonce(): void
    {
        $response = $this->get('/');

        $csp = (string) $response->headers->get('Content-Security-Policy');
        preg_match("/'nonce-([^']+)'/", $csp, $matches);

        $this->assertNotEmpty($matches[1] ?? null);
        $response->assertSee('nonce="'.$matches[1].'"', escape: false);
    }

    #[Test]
    public function hsts_is_not_sent_over_plain_http(): void
    {
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
    }

    #[Test]
    public function hsts_is_sent_over_https(): void
    {
        $this->get('https://localhost/')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    #[Test]
    public function authenticated_pages_also_carry_the_headers(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    #[Test]
    public function error_responses_also_carry_the_headers(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');
    }
}
