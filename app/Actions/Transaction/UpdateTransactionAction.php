<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use App\Services\Transaction\TransactionService;

class UpdateTransactionAction
{
    public function __construct(private readonly TransactionService $transactionService) {}

    public function __invoke(Transaction $transaction, array $data): Transaction
    {
        return $this->transactionService->update($transaction, $data);
    }
}
