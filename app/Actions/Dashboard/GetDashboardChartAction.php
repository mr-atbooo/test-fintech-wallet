<?php

namespace App\Actions\Dashboard;

use App\Models\User;
use App\Services\Dashboard\DashboardService;

class GetDashboardChartAction
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function __invoke(User $user, array $filters): array
    {
        return $this->dashboardService->chart($user, $filters['period'] ?? 'weekly');
    }
}
