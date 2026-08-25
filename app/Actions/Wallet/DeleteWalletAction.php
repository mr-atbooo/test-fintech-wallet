<?php

namespace App\Actions\Wallet;

use App\Models\Wallet;
use App\Services\Wallet\WalletService;

class DeleteWalletAction
{
    public function __construct(private readonly WalletService $walletService) {}

    public function __invoke(Wallet $wallet): void
    {
        $this->walletService->delete($wallet);
    }
}
