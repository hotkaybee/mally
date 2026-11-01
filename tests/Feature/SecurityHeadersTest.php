<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pages must not be framed by other sites (clickjacking on checkout, profile
 * and sign-in) and carry the basic hardening headers.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_storefront_pages_cannot_be_framed_and_are_not_sniffed(): void
    {
        foreach (['/', '/catalog', '/profile', '/checkout'] as $path) {
            $response = $this->get($path);

            $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'), $path);
            $this->assertStringContainsString("frame-ancestors 'self'", (string) $response->headers->get('Content-Security-Policy'), $path);
            $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'), $path);
            $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'), $path);
        }
    }

    public function test_the_admin_panel_login_carries_the_same_headers(): void
    {
        $response = $this->get('/admin/login');

        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_hsts_is_sent_only_over_https(): void
    {
        $this->assertFalse($this->get('/')->headers->has('Strict-Transport-Security'));

        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }
}
