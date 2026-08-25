<?php

namespace App\Actions\Wallet;

use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Eloquent\Collection;

class ListWalletsAction
{
    public function __construct(private readonly WalletService $walletService) {}

    public function __invoke(User $user): Collection
    {
        return $this->walletService->list($user);
    }
}
