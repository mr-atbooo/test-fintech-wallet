<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Auth\TokenService;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutUserAction
{
    public function __construct(private readonly TokenService $tokenService) {}

    public function __invoke(User $user, ?string $refreshToken): void
    {
        /** @var PersonalAccessToken|null $currentToken */
        $currentToken = $user->currentAccessToken();
        $currentToken?->delete();

        if ($refreshToken) {
            $this->tokenService->revoke($refreshToken);
        }
    }
}
