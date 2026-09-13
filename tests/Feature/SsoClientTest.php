<?php

namespace Tests\Feature;

use App\Models\SsoIdentityLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.login_portal.url' => 'http://127.0.0.1:8000',
            'services.login_portal.client_id' => 'lfews_client_id',
            'services.login_portal.client_secret' => 'lfews_client_secret',
            'services.login_portal.redirect_uri' => 'http://127.0.0.1:8001/sso/callback',
        ]);
    }

    public function test_sso_redirect_generates_session_state_and_redirects_to_portal(): void
    {
        $response = $this->get(route('sso.redirect'));

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');
        $this->assertStringContainsString('http://127.0.0.1:8000/sso/authorize', $targetUrl);
        $this->assertStringContainsString('client_id=lfews_client_id', $targetUrl);
        $this->assertStringContainsString('code_challenge=', $targetUrl);

        $this->assertTrue(session()->has('sso_state'));
        $this->assertTrue(session()->has('sso_code_verifier'));
    }

    public function test_sso_callback_rejects_invalid_state(): void
    {
        session(['sso_state' => 'valid_state_123']);

        $response = $this->get(route('sso.callback', [
            'state' => 'wrong_state_456',
            'code' => 'some_code',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(auth()->check());
    }

    public function test_sso_callback_authenticates_existing_linked_user(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        SsoIdentityLink::create([
            'user_id' => $user->id,
            'provider' => 'login_portal',
            'login_portal_user_id' => 'portal_user_1001',
        ]);

        session([
            'sso_state' => 'valid_state',
            'sso_code_verifier' => 'verifier_123',
        ]);

        Http::fake([
            'http://127.0.0.1:8000/api/sso/token' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'mocked_token',
                'user' => [
                    'id' => 'portal_user_1001',
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ], 200),
        ]);

        $response = $this->get(route('sso.callback', [
            'state' => 'valid_state',
            'code' => 'valid_auth_code',
        ]));

        $response->assertRedirect('/dashboard');
        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());
    }

    public function test_sso_callback_links_existing_local_user_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        session([
            'sso_state' => 'valid_state',
            'sso_code_verifier' => 'verifier_123',
        ]);

        Http::fake([
            'http://127.0.0.1:8000/api/sso/token' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'mocked_token',
                'user' => [
                    'id' => 'portal_user_2002',
                    'name' => 'Existing User',
                    'email' => 'existing@example.com',
                ],
            ], 200),
        ]);

        $response = $this->get(route('sso.callback', [
            'state' => 'valid_state',
            'code' => 'valid_auth_code',
        ]));

        $response->assertRedirect('/dashboard');
        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());

        $this->assertDatabaseHas('sso_identity_links', [
            'user_id' => $user->id,
            'login_portal_user_id' => 'portal_user_2002',
        ]);
    }

    public function test_sso_callback_provisions_new_user(): void
    {
        session([
            'sso_state' => 'valid_state',
            'sso_code_verifier' => 'verifier_123',
        ]);

        Http::fake([
            'http://127.0.0.1:8000/api/sso/token' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'mocked_token',
                'user' => [
                    'id' => 'portal_user_3003',
                    'name' => 'New SSO User',
                    'email' => 'newssouser@example.com',
                ],
            ], 200),
        ]);

        $response = $this->get(route('sso.callback', [
            'state' => 'valid_state',
            'code' => 'valid_auth_code',
        ]));

        $response->assertRedirect('/dashboard');
        $this->assertTrue(auth()->check());

        $this->assertDatabaseHas('users', [
            'email' => 'newssouser@example.com',
        ]);

        $this->assertDatabaseHas('sso_identity_links', [
            'login_portal_user_id' => 'portal_user_3003',
        ]);
    }

    public function test_idp_initiated_sso_callback_authenticates_user(): void
    {
        $user = User::factory()->create([
            'email' => 'idp_user@example.com',
        ]);

        Http::fake([
            'http://127.0.0.1:8000/api/sso/token' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'mocked_token',
                'user' => [
                    'id' => 'portal_user_8008',
                    'bound_user_id' => (string) $user->id,
                    'name' => 'IdP User',
                    'email' => 'idp_user@example.com',
                ],
            ], 200),
        ]);

        // Simulating IdP-initiated redirect from Login Portal Dashboard (no session sso_state pre-set)
        $response = $this->get(route('sso.callback', [
            'state' => 'idp_state_123',
            'code' => 'idp_auth_code',
        ]));

        $response->assertRedirect('/dashboard');
        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());
    }

    public function test_existing_local_login_still_works(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'local@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'local@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());
    }
}

