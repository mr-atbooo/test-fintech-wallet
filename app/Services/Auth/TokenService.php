<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\InvalidRefreshTokenException;
use App\Models\User;
use App\Repositories\RefreshTokenRepository;
use Illuminate\Support\Str;

class TokenService
{
    private const ACCESS_TOKEN_TTL_MINUTES = 60;

    private const REFRESH_TOKEN_TTL_DAYS = 30;

    public function __construct(private readonly RefreshTokenRepository $refreshTokens) {}

    /**
     * @return array{access_token: string, access_token_expires_at: \Illuminate\Support\Carbon, refresh_token: string, refresh_token_expires_at: \Illuminate\Support\Carbon}
     */
    public function issueTokenPair(User $user): array
    {
        $accessTokenExpiresAt = now()->addMinutes(self::ACCESS_TOKEN_TTL_MINUTES);
        $accessToken = $user->createToken('access-token', ['*'], $accessTokenExpiresAt)->plainTextToken;

        $refreshTokenExpiresAt = now()->addDays(self::REFRESH_TOKEN_TTL_DAYS);
        $plainRefreshToken = Str::random(64);
        $this->refreshTokens->create($user, $plainRefreshToken, $refreshTokenExpiresAt);

        return [
            'access_token' => $accessToken,
            'access_token_expires_at' => $accessTokenExpiresAt,
            'refresh_token' => $plainRefreshToken,
            'refresh_token_expires_at' => $refreshTokenExpiresAt,
        ];
    }

    /**
     * Rotate a refresh token: revoke the old one and issue a fresh pair.
     *
     * @return array{user: User, access_token: string, access_token_expires_at: \Illuminate\Support\Carbon, refresh_token: string, refresh_token_expires_at: \Illuminate\Support\Carbon}
     */
    public function rotate(string $plainRefreshToken): array
    {
        $refreshToken = $this->refreshTokens->findValidByPlainToken($plainRefreshToken);

        if (! $refreshToken) {
            throw new InvalidRefreshTokenException();
        }

        $this->refreshTokens->revoke($refreshToken);

        return ['user' => $refreshToken->user, ...$this->issueTokenPair($refreshToken->user)];
    }

    public function revoke(string $plainRefreshToken): void
    {
        $refreshToken = $this->refreshTokens->findValidByPlainToken($plainRefreshToken);

        if ($refreshToken) {
            $this->refreshTokens->revoke($refreshToken);
        }
    }
}
