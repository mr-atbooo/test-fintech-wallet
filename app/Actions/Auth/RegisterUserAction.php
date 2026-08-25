<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Auth\AuthService;

class RegisterUserAction
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * @return array{user: User, otp_code: string}
     */
    public function __invoke(array $data): array
    {
        return $this->authService->register($data);
    }
}
