<?php

namespace Tests\Feature\Concerns;

use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\WalletTypeSeeder;

trait CreatesAuthenticatedUser
{
    protected function seedReferenceData(): void
    {
        $this->seed(WalletTypeSeeder::class);
        $this->seed(CategorySeeder::class);
    }

    /**
     * Drive the real register -> verify-otp -> login flow so tests exercise
     * the actual critical path rather than bypassing it with factories.
     *
     * @return array{user: User, token: string, refresh_token: string}
     */
    protected function registerAndActivateUser(array $overrides = []): array
    {
        $payload = array_merge([
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);

        $registerResponse = $this->postJson('/api/v1/auth/register', $payload);
        $registerResponse->assertCreated();

        $otpCode = $registerResponse->json('meta.debug_otp_code');

        $this->postJson('/api/v1/auth/verify-otp', [
            'identifier' => $payload['email'],
            'code' => $otpCode,
            'purpose' => 'registration',
        ])->assertOk();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'identifier' => $payload['email'],
            'password' => $payload['password'],
        ])->assertOk();

        return [
            'user' => User::where('email', $payload['email'])->firstOrFail(),
            'token' => $loginResponse->json('data.access_token'),
            'refresh_token' => $loginResponse->json('data.refresh_token'),
        ];
    }

    /**
     * Laravel's AuthManager caches the resolved sanctum guard (and the guard
     * caches its resolved user) for the lifetime of the test's application
     * container. Since one test often simulates requests as several
     * different users, we must force re-resolution on every authenticated
     * call or a later request would silently reuse an earlier user's
     * identity. This never happens in production, where each real request
     * gets a fresh PHP process/container.
     */
    protected function authHeaders(string $token): array
    {
        $this->app['auth']->forgetGuards();

        return ['Authorization' => "Bearer {$token}"];
    }
}
