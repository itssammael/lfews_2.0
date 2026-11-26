<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SsoIdentityLink;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SsoClientController extends Controller
{
    /**
     * Redirect user to Login Portal SSO authorization endpoint.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $baseUrl = config('services.login_portal.url');
        $clientId = config('services.login_portal.client_id');
        $redirectUri = config('services.login_portal.redirect_uri') ?: route('sso.callback');

        if (empty($baseUrl) || empty($clientId) || empty($redirectUri)) {
            Log::error('SSO configuration incomplete. Missing URL, client_id, or redirect_uri.');

            return redirect()->route('login')->withErrors([
                'email' => 'SSO configuration is incomplete. Please contact your system administrator.',
            ]);
        }

        $state = Str::random(40);
        $nonce = Str::random(40);
        $codeVerifier = Str::random(64);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        session([
            'sso_state' => $state,
            'sso_nonce' => $nonce,
            'sso_code_verifier' => $codeVerifier,
        ]);

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state,
            'scope' => 'openid profile email',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'nonce' => $nonce,
        ]);

        $authorizeUrl = rtrim($baseUrl, '/').'/sso/authorize?'.$params;

        return redirect()->away($authorizeUrl);
    }

    /**
     * Handle authorization callback from Login Portal SSO.
     */
    public function callback(Request $request): RedirectResponse
    {
        // Immediately consume single-use session parameters
        $sessionState = session()->pull('sso_state');
        $sessionNonce = session()->pull('sso_nonce');
        $codeVerifier = session()->pull('sso_code_verifier');

        // Check if Login Portal returned an error
        if ($request->has('error')) {
            $error = (string) $request->query('error');
            $errorDescription = (string) $request->query('error_description', $error);
            Log::warning('SSO callback received error from Portal', [
                'error' => $error,
                'error_description' => $errorDescription,
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'SSO authorization failed: '.$errorDescription,
            ]);
        }

        $state = (string) $request->query('state');
        $code = (string) $request->query('code');

        // Validate state token against session state
        if (empty($sessionState) || empty($state) || ! hash_equals($sessionState, $state)) {
            Log::warning('SSO callback state validation failed: missing or mismatched state parameter.');

            return redirect()->route('login')->withErrors([
                'email' => 'Single Sign-On failed due to an invalid or expired state token. Please try again.',
            ]);
        }

        if (empty($code)) {
            return redirect()->route('login')->withErrors([
                'email' => 'SSO authorization code missing. Please try again.',
            ]);
        }

        $baseUrl = config('services.login_portal.url');
        $clientId = config('services.login_portal.client_id');
        $clientSecret = config('services.login_portal.client_secret');
        $redirectUri = config('services.login_portal.redirect_uri') ?: route('sso.callback');

        try {
            $tokenEndpoint = rtrim((string) $baseUrl, '/').'/api/sso/token';

            $response = Http::asForm()->timeout(15)->post($tokenEndpoint, [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'code_verifier' => $codeVerifier,
            ]);

            if (! $response->successful()) {
                Log::error('SSO token exchange failed', [
                    'status' => $response->status(),
                    'error' => $response->json('error'),
                    'message' => $response->json('message') ?? $response->json('error_description'),
                ]);

                return redirect()->route('login')->withErrors([
                    'email' => 'Failed to authenticate with Login Portal. Please try again.',
                ]);
            }

            $tokenData = $response->json();
            $ssoUser = $tokenData['user'] ?? null;

            if (! is_array($ssoUser) || empty($ssoUser['id']) || empty($ssoUser['email'])) {
                Log::error('SSO payload missing required user details');

                return redirect()->route('login')->withErrors([
                    'email' => 'Invalid identity response received from Login Portal.',
                ]);
            }

            // Verify nonce if present in response
            if (! empty($tokenData['nonce']) && ! empty($sessionNonce) && ! hash_equals((string) $sessionNonce, (string) $tokenData['nonce'])) {
                Log::warning('SSO nonce mismatch detected');

                return redirect()->route('login')->withErrors([
                    'email' => 'Single Sign-On validation failed (nonce mismatch). Please try again.',
                ]);
            }

            // 4-Tier Identity Resolution
            $localUser = $this->resolveLocalUser($ssoUser);

            if (! $localUser) {
                return redirect()->route('login')->withErrors([
                    'email' => 'No local account found matching your Login Portal credentials, and automatic provisioning is disabled.',
                ]);
            }

            // Authorization boundary check
            if (isset($localUser->status) && in_array(strtolower((string) $localUser->status), ['banned', 'inactive', 'suspended', 'disabled'])) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account is suspended or disabled. Please contact your administrator.',
                ]);
            }

            if (isset($localUser->is_active) && ! $localUser->is_active) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account is inactive. Please contact your administrator.',
                ]);
            }

            // Authenticate and regenerate session
            Auth::login($localUser, true);
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        } catch (\Throwable $e) {
            Log::error('SSO authentication exception: '.$e->getMessage());

            return redirect()->route('login')->withErrors([
                'email' => 'Unable to connect to Login Portal server. Please try again later.',
            ]);
        }
    }

    /**
     * Resolve or link local User from SSO identity payload using 4-tier resolution.
     */
    protected function resolveLocalUser(array $ssoUser): ?User
    {
        $portalUserId = (string) $ssoUser['id'];
        $boundUserId = $ssoUser['bound_user_id'] ?? null;
        $email = $ssoUser['email'] ?? null;

        $user = null;

        // Tier 1: Match local user primary key by bound_user_id
        if (! empty($boundUserId)) {
            $user = User::find($boundUserId);
        }

        // Tier 2: Match local user by login_portal_user_id == response.user.id
        if (! $user && ! empty($portalUserId)) {
            $user = User::where('login_portal_user_id', $portalUserId)->first();
        }

        // Tier 3: Match local user by email == response.user.email
        if (! $user && ! empty($email)) {
            $user = User::where('email', $email)->first();
        }

        // Tier 4: If matched in Tiers 1-3, link/update login_portal_user_id and sso_provider
        if ($user) {
            $user->login_portal_user_id = $portalUserId;
            $user->sso_provider = 'login_portal';
            $user->save();

            $this->syncSsoIdentityLink($user, $portalUserId);

            return $user;
        }

        // Tier 5: Check auto-provisioning
        $autoProvision = config('services.login_portal.auto_provision', true);
        if (! $autoProvision) {
            return null;
        }

        // Generate unique username from email prefix
        $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', (string) $email)[0])) ?: 'user';
        $username = $baseUsername;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername.$counter++;
        }

        $user = User::create([
            'name' => $ssoUser['name'] ?? 'SSO User',
            'username' => $username,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'email_verified_at' => ! empty($ssoUser['email_verified_at']) ? Carbon::parse($ssoUser['email_verified_at']) : now(),
            'login_portal_user_id' => $portalUserId,
            'sso_provider' => 'login_portal',
        ]);

        // Attach default role if available
        $userRole = Role::where('name', 'User')->orWhere('name', 'Viewer')->first();
        if ($userRole) {
            $user->roles()->attach($userRole->id);
        }

        $this->syncSsoIdentityLink($user, $portalUserId);

        return $user;
    }

    /**
     * Safely keep sso_identity_links table in sync if model and table exist.
     */
    protected function syncSsoIdentityLink(User $user, string $portalUserId): void
    {
        if (class_exists(SsoIdentityLink::class)) {
            try {
                SsoIdentityLink::updateOrCreate(
                    ['provider' => 'login_portal', 'login_portal_user_id' => $portalUserId],
                    ['user_id' => $user->id]
                );
            } catch (\Throwable $e) {
                Log::debug('SsoIdentityLink sync note: '.$e->getMessage());
            }
        }
    }
}
