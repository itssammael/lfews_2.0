<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsernameAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_username()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'username' => 'testuser123',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'testuser123',
            'email' => 'test@example.com',
        ]);
    }

    public function test_user_can_login_with_username()
    {
        $user = User::factory()->create([
            'username' => 'loginuser',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'loginuser', // The form field is named 'email'
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_email()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_empty_space_username()
    {
        $user = User::factory()->create([
            'username' => ' ',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => ' ',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }
}
