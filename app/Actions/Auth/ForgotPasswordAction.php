<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Auth\AuthService;

class ForgotPasswordAction
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * @return array{user: User, otp_code: string}
     */
    public function __invoke(string $identifier): array
    {
        return $this->authService->requestPasswordResetOtp($identifier);
    }
}
