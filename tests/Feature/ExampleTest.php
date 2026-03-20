<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_awards_returns_404(): void
    {
        $response = $this->get('/awards');

        $response->assertStatus(404);
    }

    public function test_services_returns_404(): void
    {
        $response = $this->get('/services');

        $response->assertStatus(404);
    }
}
