<?php

namespace App\Services\Dashboard;

use App\Enums\TransactionType;
use App\Models\User;
use App\Repositories\DashboardRepository;
use App\Repositories\WalletRepository;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function __construct(
        private readonly WalletRepository $wallets,
        private readonly DashboardRepository $dashboard,
    ) {}

    public function summary(User $user, ?string $dateFrom, ?string $dateTo): array
    {
        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : now()->startOfMonth();
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : now()->endOfMonth();

        return [
            'total_balance' => (float) $this->wallets->totalBalanceForUser($user),
            'total_income' => (float) $this->dashboard->sumByType($user, TransactionType::Income, $from, $to),
            'total_expense' => (float) $this->dashboard->sumByType($user, TransactionType::Expense, $from, $to),
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ];
    }

    public function chart(User $user, string $period): array
    {
        return $period === 'monthly' ? $this->monthlySeries($user) : $this->weeklySeries($user);
    }

    private function weeklySeries(User $user): array
    {
        $from = now()->subDays(6)->startOfDay();
        $to = now()->endOfDay();

        $rows = $this->dashboard->dailySeries($user, $from, $to)->groupBy('period');

        $series = [];

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $series[] = $this->bucket($date->toDateString(), $rows->get($date->toDateString()));
        }

        return $series;
    }

    private function monthlySeries(User $user): array
    {
        $from = now()->subMonths(11)->startOfMonth();
        $to = now()->endOfMonth();

        $rows = $this->dashboard->monthlySeries($user, $from, $to)->groupBy('period');

        $series = [];

        for ($month = $from->copy(); $month->lte($to); $month->addMonth()) {
            $key = $month->format('Y-m');
            $series[] = $this->bucket($key, $rows->get($key));
        }

        return $series;
    }

    private function bucket(string $label, mixed $rows): array
    {
        $rows ??= collect();

        return [
            'label' => $label,
            'income' => (float) ($rows->firstWhere('type', TransactionType::Income->value)->total ?? 0),
            'expense' => (float) ($rows->firstWhere('type', TransactionType::Expense->value)->total ?? 0),
        ];
    }
}
