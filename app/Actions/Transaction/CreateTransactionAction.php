<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use App\Models\User;
use App\Services\Transaction\TransactionService;

class CreateTransactionAction
{
    public function __construct(private readonly TransactionService $transactionService) {}

    public function __invoke(User $user, array $data): Transaction
    {
        return $this->transactionService->create($user, $data);
    }
}
