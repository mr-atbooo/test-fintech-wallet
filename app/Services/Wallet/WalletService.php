<?php

namespace App\Services\Wallet;

use App\Enums\WalletStatus;
use App\Exceptions\Wallet\WalletHasBalanceException;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\WalletRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(private readonly WalletRepository $wallets) {}

    public function list(User $user): Collection
    {
        return $this->wallets->allForUser($user);
    }

    public function create(User $user, array $data): Wallet
    {
        return DB::transaction(function () use ($user, $data) {
            $isFirstWallet = $this->wallets->countForUser($user) === 0;
            $isDefault = ($data['is_default'] ?? false) || $isFirstWallet;

            if ($isDefault) {
                $this->wallets->clearDefaultForUser($user);
            }

            return $this->wallets->create([
                'user_id' => $user->id,
                'wallet_type_id' => $data['wallet_type_id'],
                'name' => $data['name'],
                'currency' => $data['currency'] ?? 'USD',
                'balance' => 0,
                'status' => $data['status'] ?? WalletStatus::Active,
                'is_default' => $isDefault,
            ]);
        });
    }

    public function update(Wallet $wallet, array $data): Wallet
    {
        unset($data['balance']);

        return DB::transaction(function () use ($wallet, $data) {
            if (! empty($data['is_default'])) {
                $this->wallets->clearDefaultForUser($wallet->user, $wallet->id);
            }

            return $this->wallets->update($wallet, $data);
        });
    }

    public function delete(Wallet $wallet): void
    {
        if (bccomp((string) $wallet->balance, '0', 2) !== 0) {
            throw new WalletHasBalanceException();
        }

        DB::transaction(function () use ($wallet) {
            $wasDefault = $wallet->is_default;
            $user = $wallet->user;

            $this->wallets->delete($wallet);

            if ($wasDefault) {
                $nextWallet = $this->wallets->firstRemainingForUser($user, $wallet->id);
                $nextWallet && $this->wallets->makeDefault($nextWallet);
            }
        });
    }
}
