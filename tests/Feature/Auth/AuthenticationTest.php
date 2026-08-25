<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesAuthenticatedUser;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use CreatesAuthenticatedUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function test_user_can_register_and_receives_an_otp(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ahmed Test',
            'email' => 'ahmed@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'pending');
        $this->assertNotEmpty($response->json('meta.debug_otp_code'));

        $this->assertDatabaseHas('users', [
            'email' => 'ahmed@example.com',
            'status' => 'pending',
        ]);

        $user = User::where('email', 'ahmed@example.com')->firstOrFail();
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'is_default' => true,
            'balance' => 0,
        ]);
    }

    public function test_user_cannot_login_before_verifying_otp(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Pending User',
            'email' => 'pending@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'pending@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_verify_otp_fails_with_wrong_code(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Wrong Otp User',
            'email' => 'wrongotp@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'identifier' => 'wrongotp@example.com',
            'code' => '000000',
            'purpose' => 'registration',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('users', [
            'email' => 'wrongotp@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_user_can_login_after_verification_and_receives_token_pair(): void
    {
        $registered = $this->registerAndActivateUser(['email' => 'active@example.com']);

        $this->assertNotEmpty($registered['token']);
        $this->assertNotEmpty($registered['refresh_token']);
        $this->assertSame('active', $registered['user']->fresh()->status->value);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->registerAndActivateUser(['email' => 'wrongpass@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'wrongpass@example.com',
            'password' => 'not-the-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_fetch_me(): void
    {
        $session = $this->registerAndActivateUser(['email' => 'me@example.com']);

        $response = $this->getJson('/api/v1/auth/me', $this->authHeaders($session['token']));

        $response->assertOk();
        $response->assertJsonPath('data.email', 'me@example.com');
    }

    public function test_unauthenticated_request_to_me_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_user_can_refresh_token_and_old_refresh_token_is_invalidated(): void
    {
        $session = $this->registerAndActivateUser(['email' => 'refresh@example.com']);

        $refreshResponse = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $session['refresh_token'],
        ]);

        $refreshResponse->assertOk();
        $newAccessToken = $refreshResponse->json('data.access_token');
        $this->assertNotEmpty($newAccessToken);
        $this->assertNotSame($session['token'], $newAccessToken);

        $reuseResponse = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $session['refresh_token'],
        ]);

        $reuseResponse->assertStatus(401);
    }

    public function test_user_can_logout_and_token_is_revoked(): void
    {
        $session = $this->registerAndActivateUser(['email' => 'logout@example.com']);

        $this->postJson('/api/v1/auth/logout', [], $this->authHeaders($session['token']))
            ->assertOk();

        $this->getJson('/api/v1/auth/me', $this->authHeaders($session['token']))
            ->assertStatus(401);
    }
}
