<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.login_portal.url' => 'https://loginportal.ink',
            'services.login_portal.client_id' => 'client_CU0m3qFmZmikyOdH',
            'services.login_portal.client_secret' => '1bsBBzWZvHbhpPrWVoNHUiy0fr2vByW5VNEhq7BTa8Wb9RUykSIwNIoNVQkLwyuy',
            'services.login_portal.redirect_uri' => 'https://lfews.lguportal.ink/sso/callback',
            'services.login_portal.auto_provision' => true,
        ]);
    }

    public function test_sso_redirect_generates_session_tokens_and_redirects_with_pkce_and_nonce(): void
    {
        $response = $this->get(route('sso.redirect'));

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');

        $this->assertStringContainsString('https://loginportal.ink/sso/authorize', $targetUrl);
        $this->assertStringContainsString('client_id=client_CU0m3qFmZmikyOdH', $targetUrl);
        $this->assertStringContainsString('redirect_uri='.urlencode('https://lfews.lguportal.ink/sso/callback'), $targetUrl);
        $this->assertStringContainsString('response_type=code', $targetUrl);
        $this->assertStringContainsString('code_challenge_method=S256', $targetUrl);
        $this->assertStringContainsString('code_challenge=', $targetUrl);
        $this->assertStringContainsString('nonce=', $targetUrl);
        $this->assertStringContainsString('state=', $targetUrl);

        $this->assertTrue(session()->has('sso_state'));
        $this->assertTrue(session()->has('sso_nonce'));
        $this->assertTrue(session()->has('sso_code_verifier'));
    }

    public function test_sso_redirect_fails_gracefully_when_config_is_missing(): void
    {
        config(['services.login_portal.client_id' => null]);

        $response = $this->get(route('sso.redirect'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
    }

    public function test_sso_callback_rejects_missing_or_mismatched_state(): void
    {
        session([
            'sso_state' => 'stored_secret_state_12345678901234567890',
            'sso_code_verifier' => 'dummy_verifier',
        ]);

        $response = $this->get(route('sso.callback', [
            'state' => 'forged_or_wrong_state_00000000000000000000',
            'code' => 'auth_code_123',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(auth()->check());
    }

    public function test_sso_callback_handles_portal_error_gracefully(): void
    {
        $response = $this->get(route('sso.callback', [
            'error' => 'access_denied',
            'error_description' => 'User account is restricted by Login Portal.',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(auth()->check());
    }

    public function test_sso_callback_rejects_missing_authorization_code(): void
    {
        session([
            'sso_state' => 'valid_state_123456789012345678901234567890',
            'sso_code_verifier' => 'dummy_verifier',
        ]);

        $response = $this->get(route('sso.callback', [
            'state' => 'valid_state_123456789012345678901234567890',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(auth()->check());
    }

    public function test_sso_callback_handles_token_exchange_error_responses(): void
    {
        session([
            'sso_state' => 'valid_state_123456789012345678901234567890',
            'sso_code_verifier' => 'dummy_verifier',
        ]);

        Http::fake([
            'https://loginportal.ink/api/sso/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Authorization code has expired.',
            ], 400),
        ]);

        $response = $this->get(route('sso.callback', [
            'state' => 'valid_state_123456789012345678901234567890',
            'code' => 'expired_code_xyz',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(auth()->check());
    }

    public function test_sso_callback_provisions_new_user_when_auto_provision_is_true(): void
    {
        session([
            'sso_state' => 'valid_state_token_12345678901234567890',
            'sso_nonce' => 'valid_nonce_token_12345678901234567890',
            'sso_code_verifier' => 'code_verifier_1234567890',
        ]);

        Http::fake([
            'https://loginportal.ink/api/sso/token' => Http::response([
                'access_token' => 'ephemeral_access_token_80_chars',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'nonce' => 'valid_nonce_token_12345678901234567890',
                'user' => [
                    'id' => 991,
                    'name' => 'Maria Santos',
                    'email' => 'maria.santos@lgu.gov.ph',
                    'email_verified_at' => '2026-09-14T08:00:00.000000Z',
                ],
            ], 200),
        ]);

        $response = $this->get(route('sso.callback', [
            'state' => 'valid_state_token_12345678901234567890',
            'code' => 'valid_code_991',
        ]));

        $response->assertRedirect('/dashboard');
        $this->assertTrue(auth()->check());
        $this->assertEquals('maria.santos@lgu.gov.ph', auth()->user()->email);

        $this->assertDatabaseHas('users', [
            'email' => 'maria.santos@lgu.gov.ph',
            'login_portal_user_id' => 991,
            'sso_provider' => 'login_portal',
        ]);
    }

    public function test_sso_callback_matches_existing_user_by_bound_user_id(): void
    {
        $existingUser = User::factory()->create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.ph',
        ]);

        session([
            'sso_state' => 'state_abc',
            'sso_code_verifier' => 'verifier_xyz',
        ]);

        Http::fake([
            'https://loginportal.ink/api/sso/token' => Http::response([
                'access_token' => 'mock_token',
                'token_type' => 'Bearer',
                'user' => [
                    'id' => 505,
                    'bound_user_id' => (string) $existingUser->id,
                    'name' => 'Juan Dela Cruz Updated',
                    'email' => 'juan.portal@example.ph',
                ],
            ], 200),
        ]);

        $response = $this->get(route('sso.callback', [
            'state' => 'state_abc',
            'code' => 'code_505',
        ]));

        $response->assertRedirect('/dashboard');
        $this->assertTrue(auth()->check());
        $this->assertEquals($existingUser->id, auth()->id());

        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'login_portal_user_id' => 505,
            'sso_provider' => 'login_portal',
        ]);
    }

    public function test_sso_callback_matches_existing_user_by_login_portal_user_id(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'portaluser@example.com',
            'login_portal_user_id' => 777,
            'sso_provider' => 'login_portal',
        ]);

        session([
            'sso_state' => 'state_abc',
            'sso_code_verifier' => 'verifier_xyz',
        ]);

        Http::fake([
            'https://loginportal.ink/api/sso/token' => Http::response([
                'access_token' => 'mock_token',
                'token_type' => 'Bearer',
                'user' => [
                    'id' => 777,
                    'name' => 'Portal User',
                    'email' => 'portaluser@example.com',
                ],
            ], 200),
        ]);

        $response = $this->get(route('sso.callback', [
            'state' => 'state_abc',
            'code' => 'code_777',
        ]));

        $response->assertRedirect('/dashboard');
        $this->assertTrue(auth()->check());
        $this->assertEquals($existingUser->id, auth()->id());
    }

    public function test_sso_callback_links_existing_user_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'existing.employee@lgu.gov.ph',
            'login_portal_user_id' => null,
        ]);

        session([
            'sso_state' => 'state_email_link',
            'sso_code_verifier' => 'verifier_email_link',
        ]);

        Http::fake([
            'https://loginportal.ink/api/sso/token' => Http::response([
                'access_token' => 'mock_token',
                'token_type' => 'Bearer',
                'user' => [
                    'id' => 888,
                    'name' => 'Existing Employee',
                    'email' => 'existing.employee@lgu.gov.ph',
                ],
            ], 200),
        ]);

        $response = $this->get(route('sso.callback', [
            'state' => 'state_email_link',
            'code' => 'code_888',
        ]));

        $response->assertRedirect('/dashboard');
        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());

        $user->refresh();
        $this->assertEquals(888, $user->login_portal_user_id);
        $this->assertEquals('login_portal', $user->sso_provider);
    }

    public function test_sso_callback_denies_unprovisioned_users_when_auto_provision_is_false(): void
    {
        config(['services.login_portal.auto_provision' => false]);

        session([
            'sso_state' => 'state_no_provision',
            'sso_code_verifier' => 'verifier_no_provision',
        ]);

        Http::fake([
            'https://loginportal.ink/api/sso/token' => Http::response([
                'access_token' => 'mock_token',
                'token_type' => 'Bearer',
                'user' => [
                    'id' => 9999,
                    'name' => 'Unprovisioned User',
                    'email' => 'unprovisioned@example.com',
                ],
            ], 200),
        ]);

        $response = $this->get(route('sso.callback', [
            'state' => 'state_no_provision',
            'code' => 'code_9999',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(auth()->check());
        $this->assertDatabaseMissing('users', ['email' => 'unprovisioned@example.com']);
    }

    public function test_api_verify_credentials_succeeds_for_valid_credentials_and_secret(): void
    {
        $user = User::factory()->create([
            'name' => 'Officer Reyes',
            'username' => 'officer_reyes',
            'email' => 'reyes@lgu.gov.ph',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $response = $this->postJson('/api/sso/verify-credentials', [
            'username' => 'reyes@lgu.gov.ph',
            'password' => 'CorrectPassword123!',
            'client_secret' => '1bsBBzWZvHbhpPrWVoNHUiy0fr2vByW5VNEhq7BTa8Wb9RUykSIwNIoNVQkLwyuy',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => true,
            'user_id' => (string) $user->id,
            'name' => 'Officer Reyes',
            'email' => 'reyes@lgu.gov.ph',
        ]);
    }

    public function test_api_verify_credentials_succeeds_by_username(): void
    {
        $user = User::factory()->create([
            'name' => 'Officer Reyes',
            'username' => 'officer_reyes',
            'email' => 'reyes2@lgu.gov.ph',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $response = $this->postJson('/api/sso/verify-credentials', [
            'username' => 'officer_reyes',
            'password' => 'CorrectPassword123!',
            'client_secret' => '1bsBBzWZvHbhpPrWVoNHUiy0fr2vByW5VNEhq7BTa8Wb9RUykSIwNIoNVQkLwyuy',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => true,
            'user_id' => (string) $user->id,
            'name' => 'Officer Reyes',
            'email' => 'reyes2@lgu.gov.ph',
        ]);
    }

    public function test_api_verify_credentials_rejects_invalid_client_secret(): void
    {
        $response = $this->postJson('/api/sso/verify-credentials', [
            'username' => 'any@example.com',
            'password' => 'any_password',
            'client_secret' => 'wrong_or_invalid_secret',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'valid' => false,
            'message' => 'Invalid client secret.',
        ]);
    }

    public function test_api_verify_credentials_rejects_incorrect_password(): void
    {
        User::factory()->create([
            'email' => 'user.auth@lgu.gov.ph',
            'password' => Hash::make('RealPassword123!'),
        ]);

        $response = $this->postJson('/api/sso/verify-credentials', [
            'username' => 'user.auth@lgu.gov.ph',
            'password' => 'WrongPassword!',
            'client_secret' => '1bsBBzWZvHbhpPrWVoNHUiy0fr2vByW5VNEhq7BTa8Wb9RUykSIwNIoNVQkLwyuy',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'valid' => false,
            'message' => 'Invalid username or password.',
        ]);
    }

    public function test_fallback_verify_credentials_route_works(): void
    {
        $user = User::factory()->create([
            'name' => 'Fallback Officer',
            'email' => 'fallback@lgu.gov.ph',
            'password' => Hash::make('SecretPass123!'),
        ]);

        $response = $this->post('/sso/verify-credentials', [
            'username' => 'fallback@lgu.gov.ph',
            'password' => 'SecretPass123!',
            'client_secret' => '1bsBBzWZvHbhpPrWVoNHUiy0fr2vByW5VNEhq7BTa8Wb9RUykSIwNIoNVQkLwyuy',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => true,
            'user_id' => (string) $user->id,
            'name' => 'Fallback Officer',
            'email' => 'fallback@lgu.gov.ph',
        ]);
    }

    public function test_existing_local_login_flow_remains_operational(): void
    {
        $user = User::factory()->create([
            'username' => 'localtester',
            'email' => 'localtester@example.com',
            'password' => Hash::make('LocalPassword123!'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'localtester@example.com',
            'password' => 'LocalPassword123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());
    }
}
