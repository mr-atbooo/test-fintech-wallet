<?php

namespace App\Actions\Auth;

use App\Services\Auth\TokenService;

class RefreshTokenAction
{
    public function __construct(private readonly TokenService $tokenService) {}

    /**
     * @return array{access_token: string, access_token_expires_at: \Illuminate\Support\Carbon, refresh_token: string, refresh_token_expires_at: \Illuminate\Support\Carbon}
     */
    public function __invoke(string $plainRefreshToken): array
    {
        return $this->tokenService->rotate($plainRefreshToken);
    }
}
