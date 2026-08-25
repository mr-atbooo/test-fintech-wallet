<?php

namespace App\Repositories;

use App\Models\Transfer;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class TransferRepository
{
    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return Transfer::query()
            ->where('user_id', $user->id)
            ->with(['fromWallet.walletType', 'toWallet.walletType'])
            ->latest('id')
            ->paginate($perPage);
    }

    public function create(array $attributes): Transfer
    {
        return Transfer::create($attributes);
    }
}
