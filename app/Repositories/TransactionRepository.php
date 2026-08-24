<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionRepository
{
    public function paginateForUser(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        return Transaction::query()
            ->where('user_id', $user->id)
            ->with(['wallet.walletType', 'category'])
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($filters['wallet_id'] ?? null, fn ($query, $id) => $query->where('wallet_id', $id))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('transaction_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('transaction_date', '<=', $date))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('note', 'like', '%'.$search.'%'))
            ->latest('transaction_date')
            ->paginate($perPage);
    }

    public function create(array $attributes): Transaction
    {
        return Transaction::create($attributes);
    }

    public function save(Transaction $transaction, array $attributes): Transaction
    {
        $transaction->fill($attributes)->save();

        return $transaction;
    }

    public function delete(Transaction $transaction): void
    {
        $transaction->delete();
    }
}
