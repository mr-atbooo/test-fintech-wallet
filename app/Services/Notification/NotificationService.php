<?php

namespace App\Services\Notification;

use App\Enums\NotificationType;
use App\Enums\TransactionType;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\NotificationRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    private const MAX_PER_PAGE = 100;

    /**
     * Wallets crossing at or below this balance trigger a one-time
     * low-balance notification (only on the crossing, not every
     * subsequent transaction while it stays low).
     */
    private const LOW_BALANCE_THRESHOLD = '100.00';

    public function __construct(private readonly NotificationRepository $notifications) {}

    public function notifyTransactionCreated(User $user, Transaction $transaction, Wallet $wallet): void
    {
        $titleKey = $transaction->type === TransactionType::Income
            ? 'api.notification.transaction_income_title'
            : 'api.notification.transaction_expense_title';

        $this->notifications->create(
            $user,
            NotificationType::Transaction,
            __($titleKey),
            __('api.notification.transaction_body', [
                'amount' => number_format((float) $transaction->amount, 2),
                'currency' => $wallet->currency,
                'wallet' => $wallet->name,
            ]),
            ['transaction_id' => $transaction->id, 'wallet_id' => $transaction->wallet_id]
        );
    }

    public function notifyTransferCompleted(User $user, Transfer $transfer, Wallet $fromWallet, Wallet $toWallet): void
    {
        $this->notifications->create(
            $user,
            NotificationType::Transaction,
            __('api.notification.transfer_title'),
            __('api.notification.transfer_body', [
                'amount' => number_format((float) $transfer->amount, 2),
                'currency' => $fromWallet->currency,
                'from' => $fromWallet->name,
                'to' => $toWallet->name,
            ]),
            ['transfer_id' => $transfer->id]
        );
    }

    public function notifyLowBalanceIfCrossed(User $user, Wallet $wallet, string $balanceBefore, string $balanceAfter): void
    {
        $wasAboveThreshold = bccomp($balanceBefore, self::LOW_BALANCE_THRESHOLD, 2) > 0;
        $isNowAtOrBelowThreshold = bccomp($balanceAfter, self::LOW_BALANCE_THRESHOLD, 2) <= 0;

        if (! ($wasAboveThreshold && $isNowAtOrBelowThreshold)) {
            return;
        }

        $this->notifications->create(
            $user,
            NotificationType::LowBalance,
            __('api.notification.low_balance_title'),
            __('api.notification.low_balance_body', [
                'wallet' => $wallet->name,
                'balance' => number_format((float) $balanceAfter, 2),
                'currency' => $wallet->currency,
            ]),
            ['wallet_id' => $wallet->id]
        );
    }

    public function list(User $user, array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 20), self::MAX_PER_PAGE);

        return $this->notifications->paginateForUser($user, $filters, $perPage);
    }

    public function markAsRead(Notification $notification): Notification
    {
        return $this->notifications->markRead($notification);
    }

    public function markAllAsRead(User $user): void
    {
        $this->notifications->markAllReadForUser($user);
    }
}
