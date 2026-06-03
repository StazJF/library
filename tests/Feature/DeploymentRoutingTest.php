<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeploymentRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_entrypoints_are_reachable_without_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertStatus(200);
        $this->get('/create-admin')->assertStatus(200);
    }

    public function test_protected_pages_redirect_guests_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/books/catalog')->assertRedirect('/login');
        $this->get('/utilities')->assertRedirect('/login');
    }

    public function test_security_headers_are_applied_to_https_responses(): void
    {
        $response = $this->get('https://library.test/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
