<?php

namespace App\Repositories;

use App\Enums\TransactionType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardRepository
{
    /**
     * Transfers move money between a user's own wallets and are excluded
     * here: they net to zero and would otherwise inflate income/expense
     * totals with money the user never actually earned or spent.
     */
    public function sumByType(User $user, TransactionType $type, Carbon $from, Carbon $to): string
    {
        return (string) DB::table('transactions')
            ->where('user_id', $user->id)
            ->where('type', $type->value)
            ->whereNull('transfer_id')
            ->whereNull('deleted_at')
            ->whereBetween('transaction_date', [$from, $to])
            ->sum('amount');
    }

    public function dailySeries(User $user, Carbon $from, Carbon $to): Collection
    {
        return DB::table('transactions')
            ->where('user_id', $user->id)
            ->whereNull('transfer_id')
            ->whereNull('deleted_at')
            ->whereBetween('transaction_date', [$from, $to])
            ->selectRaw('DATE(transaction_date) as period, type, SUM(amount) as total')
            ->groupBy('period', 'type')
            ->get();
    }

    public function monthlySeries(User $user, Carbon $from, Carbon $to): Collection
    {
        return DB::table('transactions')
            ->where('user_id', $user->id)
            ->whereNull('transfer_id')
            ->whereNull('deleted_at')
            ->whereBetween('transaction_date', [$from, $to])
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as period, type, SUM(amount) as total")
            ->groupBy('period', 'type')
            ->get();
    }
}
