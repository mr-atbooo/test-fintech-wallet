<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Auth\TokenService;

class LoginUserAction
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly TokenService $tokenService,
    ) {}

    /**
     * @return array{user: User, access_token: string, access_token_expires_at: \Illuminate\Support\Carbon, refresh_token: string, refresh_token_expires_at: \Illuminate\Support\Carbon}
     */
    public function __invoke(string $identifier, string $password): array
    {
        $user = $this->authService->findUserForLogin($identifier, $password);

        return ['user' => $user, ...$this->tokenService->issueTokenPair($user)];
    }
}
