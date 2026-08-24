<?php

namespace App\Services\Transaction;

use App\Enums\TransactionType;
use App\Exceptions\Transaction\TransferLinkedTransactionException;
use App\Exceptions\Wallet\InsufficientBalanceException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Services\Notification\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    private const MAX_PER_PAGE = 100;

    public function __construct(
        private readonly TransactionRepository $transactions,
        private readonly WalletRepository $wallets,
        private readonly NotificationService $notifications,
    ) {}

    public function list(User $user, array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 20), self::MAX_PER_PAGE);

        return $this->transactions->paginateForUser($user, $filters, $perPage);
    }

    public function create(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data) {
            $wallet = $this->wallets->lockById($data['wallet_id']);
            $type = TransactionType::from($data['type']);
            $amount = (string) $data['amount'];

            $balanceBefore = (string) $wallet->balance;
            $balanceAfter = $this->applyDelta($balanceBefore, $type, $amount);

            $this->assertSufficientBalance($wallet, $balanceAfter);

            $transaction = $this->transactions->create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'category_id' => $data['category_id'] ?? null,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'note' => $data['note'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now(),
            ]);

            $this->wallets->setBalance($wallet, $balanceAfter);

            $this->notifications->notifyTransactionCreated($user, $transaction, $wallet);
            $this->notifications->notifyLowBalanceIfCrossed($user, $wallet, $balanceBefore, $balanceAfter);

            return $transaction;
        });
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        if ($transaction->transfer_id !== null) {
            throw new TransferLinkedTransactionException();
        }

        return DB::transaction(function () use ($transaction, $data) {
            $newWalletId = isset($data['wallet_id']) ? (int) $data['wallet_id'] : $transaction->wallet_id;
            $newType = isset($data['type']) ? TransactionType::from($data['type']) : $transaction->type;
            $newAmount = isset($data['amount']) ? (string) $data['amount'] : (string) $transaction->amount;
            $oldDelta = $this->signedDelta($transaction->type, (string) $transaction->amount);

            if ($newWalletId === $transaction->wallet_id) {
                $wallet = $this->wallets->lockById($transaction->wallet_id);

                $balanceBefore = bcsub((string) $wallet->balance, $oldDelta, 2);
                $balanceAfter = $this->applyDelta($balanceBefore, $newType, $newAmount);

                $this->assertSufficientBalance($wallet, $balanceAfter);

                $this->transactions->save($transaction, [
                    ...$data,
                    'type' => $newType,
                    'amount' => $newAmount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                ]);

                $this->wallets->setBalance($wallet, $balanceAfter);
            } else {
                $locked = $this->wallets->lockManyByIds([$transaction->wallet_id, $newWalletId]);
                $oldWallet = $locked[$transaction->wallet_id];
                $newWallet = $locked[$newWalletId];

                $oldWalletBalance = bcsub((string) $oldWallet->balance, $oldDelta, 2);
                $this->assertSufficientBalance($oldWallet, $oldWalletBalance);

                $newBalanceBefore = (string) $newWallet->balance;
                $newBalanceAfter = $this->applyDelta($newBalanceBefore, $newType, $newAmount);
                $this->assertSufficientBalance($newWallet, $newBalanceAfter);

                $this->transactions->save($transaction, [
                    ...$data,
                    'wallet_id' => $newWallet->id,
                    'type' => $newType,
                    'amount' => $newAmount,
                    'balance_before' => $newBalanceBefore,
                    'balance_after' => $newBalanceAfter,
                ]);

                $this->wallets->setBalance($oldWallet, $oldWalletBalance);
                $this->wallets->setBalance($newWallet, $newBalanceAfter);
            }

            return $transaction->fresh(['wallet.walletType', 'category']);
        });
    }

    public function delete(Transaction $transaction): void
    {
        if ($transaction->transfer_id !== null) {
            throw new TransferLinkedTransactionException();
        }

        DB::transaction(function () use ($transaction) {
            $wallet = $this->wallets->lockById($transaction->wallet_id);

            $delta = $this->signedDelta($transaction->type, (string) $transaction->amount);
            $balanceAfterReversal = bcsub((string) $wallet->balance, $delta, 2);

            $this->assertSufficientBalance($wallet, $balanceAfterReversal);

            $this->transactions->delete($transaction);
            $this->wallets->setBalance($wallet, $balanceAfterReversal);
        });
    }

    private function applyDelta(string $balance, TransactionType $type, string $amount): string
    {
        return $type === TransactionType::Income
            ? bcadd($balance, $amount, 2)
            : bcsub($balance, $amount, 2);
    }

    /**
     * Signed amount: positive for income, negative for expense. Subtracting
     * this from a balance reverses the effect the transaction had on it.
     */
    private function signedDelta(TransactionType $type, string $amount): string
    {
        return $type === TransactionType::Income ? $amount : bcmul($amount, '-1', 2);
    }

    private function assertSufficientBalance(Wallet $wallet, string $balanceAfter): void
    {
        if (bccomp($balanceAfter, '0', 2) < 0 && ! $wallet->walletType->code->allowsNegativeBalance()) {
            throw new InsufficientBalanceException();
        }
    }
}
