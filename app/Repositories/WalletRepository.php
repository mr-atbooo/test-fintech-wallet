<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletType;
use Illuminate\Database\Eloquent\Collection;

class WalletRepository
{
    public function create(array $attributes): Wallet
    {
        return Wallet::create($attributes);
    }

    public function createDefault(User $user, WalletType $walletType, string $currency): Wallet
    {
        return $this->create([
            'user_id' => $user->id,
            'wallet_type_id' => $walletType->id,
            'name' => $walletType->name,
            'currency' => $currency,
            'balance' => 0,
            'is_default' => true,
        ]);
    }

    public function allForUser(User $user): Collection
    {
        return $user->wallets()->with('walletType')->get();
    }

    public function countForUser(User $user): int
    {
        return $user->wallets()->count();
    }

    public function update(Wallet $wallet, array $attributes): Wallet
    {
        $wallet->update($attributes);

        return $wallet->refresh();
    }

    public function delete(Wallet $wallet): void
    {
        $wallet->delete();
    }

    public function clearDefaultForUser(User $user, ?int $exceptWalletId = null): void
    {
        $user->wallets()
            ->where('is_default', true)
            ->when($exceptWalletId, fn ($query) => $query->whereKeyNot($exceptWalletId))
            ->update(['is_default' => false]);
    }

    public function firstRemainingForUser(User $user, int $exceptWalletId): ?Wallet
    {
        return $user->wallets()
            ->whereKeyNot($exceptWalletId)
            ->oldest('id')
            ->first();
    }

    public function makeDefault(Wallet $wallet): void
    {
        $wallet->update(['is_default' => true]);
    }

    /**
     * Lock a wallet row for update. Must be called inside a DB transaction.
     */
    public function lockById(int $id): ?Wallet
    {
        return Wallet::with('walletType')->whereKey($id)->lockForUpdate()->first();
    }

    /**
     * Lock multiple wallet rows for update, keyed by id. Must be called
     * inside a DB transaction. Ids are locked in ascending order to avoid
     * deadlocks when two requests touch the same pair of wallets.
     *
     * @param  array<int>  $ids
     * @return Collection<int, Wallet>
     */
    public function lockManyByIds(array $ids): Collection
    {
        return Wallet::with('walletType')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    public function setBalance(Wallet $wallet, string $balance): void
    {
        $wallet->update(['balance' => $balance]);
    }

    public function totalBalanceForUser(User $user): string
    {
        return (string) $user->wallets()->sum('balance');
    }
}
