<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use App\Services\Transaction\TransactionService;

class DeleteTransactionAction
{
    public function __construct(private readonly TransactionService $transactionService) {}

    public function __invoke(Transaction $transaction): void
    {
        $this->transactionService->delete($transaction);
    }
}
