<?php

namespace App\Actions\Transaction;

use App\Models\User;
use App\Services\Transaction\TransactionService;
use Illuminate\Pagination\LengthAwarePaginator;

class ListTransactionsAction
{
    public function __construct(private readonly TransactionService $transactionService) {}

    public function __invoke(User $user, array $filters): LengthAwarePaginator
    {
        return $this->transactionService->list($user, $filters);
    }
}
