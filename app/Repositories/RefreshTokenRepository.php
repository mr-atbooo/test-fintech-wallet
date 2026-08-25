<?php

namespace App\Repositories;

use App\Models\RefreshToken;
use App\Models\User;
use DateTimeInterface;

class RefreshTokenRepository
{
    public function create(User $user, string $plainToken, DateTimeInterface $expiresAt): RefreshToken
    {
        return RefreshToken::create([
            'user_id' => $user->id,
            'token' => $this->hash($plainToken),
            'expires_at' => $expiresAt,
            'revoked' => false,
        ]);
    }

    public function findValidByPlainToken(string $plainToken): ?RefreshToken
    {
        return RefreshToken::where('token', $this->hash($plainToken))
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function revoke(RefreshToken $refreshToken): void
    {
        $refreshToken->update(['revoked' => true]);
    }

    /**
     * Refresh tokens are high-entropy random strings, not user-chosen
     * secrets, so a fast deterministic hash gives us indexed lookups
     * (the same approach Sanctum itself uses for access tokens).
     */
    private function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
