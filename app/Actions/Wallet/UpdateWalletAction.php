<?php

namespace App\Actions\Wallet;

use App\Models\Wallet;
use App\Services\Wallet\WalletService;

class UpdateWalletAction
{
    public function __construct(private readonly WalletService $walletService) {}

    public function __invoke(Wallet $wallet, array $data): Wallet
    {
        return $this->walletService->update($wallet, $data);
    }
}
