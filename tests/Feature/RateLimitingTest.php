<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    /**
     * Test that the global rate limiter limits requests after 100 hits.
     */
    public function test_global_rate_limiter_limits_requests()
    {
        // Make 100 requests successfully (should redirect to login)
        for ($i = 0; $i < 100; $i++) {
            $response = $this->get('/');
            $response->assertStatus(302);
        }

        // The 101st request should be throttled
        $response = $this->get('/');
        $response->assertStatus(429);
    }

    /**
     * Test that the login rate limiter has correct limits (5 attempts per 15 minutes).
     */
    public function test_login_rate_limiter_configuration()
    {
        $limiter = RateLimiter::limiter('login');
        $this->assertNotNull($limiter, 'Login rate limiter is not registered.');

        $request = Request::create('/login', 'POST', ['email' => 'test@example.com']);
        $limit = $limiter($request);

        $this->assertInstanceOf(\Illuminate\Cache\RateLimiting\Limit::class, $limit);
        $this->assertEquals(5, $limit->maxAttempts);
        $this->assertEquals(900, $limit->decaySeconds); // 15 minutes in seconds
    }
}
