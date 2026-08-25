<?php

namespace App\Exceptions\Wallet;

use App\Exceptions\Api\ApiException;

class InsufficientBalanceException extends ApiException
{
    public function __construct()
    {
        parent::__construct(__('api.wallet.insufficient_balance'), 422);
    }
}
