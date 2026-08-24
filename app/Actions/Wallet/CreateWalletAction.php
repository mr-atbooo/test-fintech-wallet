<?php

namespace App\Actions\Wallet;

use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\WalletService;

class CreateWalletAction
{
    public function __construct(private readonly WalletService $walletService) {}

    public function __invoke(User $user, array $data): Wallet
    {
        return $this->walletService->create($user, $data);
    }
}
