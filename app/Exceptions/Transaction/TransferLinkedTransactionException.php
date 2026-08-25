<?php

namespace App\Exceptions\Transaction;

use App\Exceptions\Api\ApiException;

class TransferLinkedTransactionException extends ApiException
{
    public function __construct()
    {
        parent::__construct(__('api.transaction.transfer_linked'), 422);
    }
}
