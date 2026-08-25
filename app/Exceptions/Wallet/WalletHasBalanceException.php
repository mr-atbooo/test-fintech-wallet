<?php

namespace App\Exceptions\Wallet;

use App\Exceptions\Api\ApiException;

class WalletHasBalanceException extends ApiException
{
    public function __construct()
    {
        parent::__construct(__('api.wallet.has_balance'), 422);
    }
}
