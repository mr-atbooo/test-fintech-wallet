<?php

namespace App\Services\Transfer;

use App\Enums\TransactionType;
use App\Exceptions\Wallet\InsufficientBalanceException;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\TransactionRepository;
use App\Repositories\TransferRepository;
use App\Repositories\WalletRepository;
use App\Services\Notification\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransferService
{
    private const MAX_PER_PAGE = 100;

    public function __construct(
        private readonly TransferRepository $transfers,
        private readonly WalletRepository $wallets,
        private readonly TransactionRepository $transactions,
        private readonly NotificationService $notifications,
    ) {}

    public function list(User $user, array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 20), self::MAX_PER_PAGE);

        return $this->transfers->paginateForUser($user, $perPage);
    }

    public function create(User $user, array $data): Transfer
    {
        return DB::transaction(function () use ($user, $data) {
            $fromWalletId = (int) $data['from_wallet_id'];
            $toWalletId = (int) $data['to_wallet_id'];
            $amount = (string) $data['amount'];

            $locked = $this->wallets->lockManyByIds([$fromWalletId, $toWalletId]);
            $fromWallet = $locked[$fromWalletId];
            $toWallet = $locked[$toWalletId];

            $fromBalanceBefore = (string) $fromWallet->balance;
            $fromBalanceAfter = bcsub($fromBalanceBefore, $amount, 2);
            $this->assertSufficientBalance($fromWallet, $fromBalanceAfter);

            $toBalanceBefore = (string) $toWallet->balance;
            $toBalanceAfter = bcadd($toBalanceBefore, $amount, 2);

            $transfer = $this->transfers->create([
                'user_id' => $user->id,
                'from_wallet_id' => $fromWallet->id,
                'to_wallet_id' => $toWallet->id,
                'amount' => $amount,
                'note' => $data['note'] ?? null,
            ]);

            $this->transactions->create([
                'user_id' => $user->id,
                'wallet_id' => $fromWallet->id,
                'transfer_id' => $transfer->id,
                'type' => TransactionType::Expense,
                'amount' => $amount,
                'balance_before' => $fromBalanceBefore,
                'balance_after' => $fromBalanceAfter,
                'note' => $data['note'] ?? "Transfer to {$toWallet->name}",
                'transaction_date' => now(),
            ]);

            $this->transactions->create([
                'user_id' => $user->id,
                'wallet_id' => $toWallet->id,
                'transfer_id' => $transfer->id,
                'type' => TransactionType::Income,
                'amount' => $amount,
                'balance_before' => $toBalanceBefore,
                'balance_after' => $toBalanceAfter,
                'note' => $data['note'] ?? "Transfer from {$fromWallet->name}",
                'transaction_date' => now(),
            ]);

            $this->wallets->setBalance($fromWallet, $fromBalanceAfter);
            $this->wallets->setBalance($toWallet, $toBalanceAfter);

            $this->notifications->notifyTransferCompleted($user, $transfer, $fromWallet, $toWallet);
            $this->notifications->notifyLowBalanceIfCrossed($user, $fromWallet, $fromBalanceBefore, $fromBalanceAfter);

            return $transfer->load(['fromWallet.walletType', 'toWallet.walletType']);
        });
    }

    private function assertSufficientBalance(Wallet $wallet, string $balanceAfter): void
    {
        if (bccomp($balanceAfter, '0', 2) < 0 && ! $wallet->walletType->code->allowsNegativeBalance()) {
            throw new InsufficientBalanceException();
        }
    }
}
