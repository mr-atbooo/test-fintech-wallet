<?php

namespace App\Actions\Auth;

use App\Enums\OtpPurpose;
use App\Models\User;
use App\Services\Auth\AuthService;

class VerifyOtpAction
{
    public function __construct(private readonly AuthService $authService) {}

    public function __invoke(string $identifier, string $code, OtpPurpose $purpose, ?string $newPassword): User
    {
        return match ($purpose) {
            OtpPurpose::Registration => $this->authService->verifyRegistrationOtp($identifier, $code),
            OtpPurpose::PasswordReset => $this->authService->verifyPasswordResetOtp($identifier, $code, $newPassword),
        };
    }
}
