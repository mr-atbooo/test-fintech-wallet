<?php

namespace App\Actions\Dashboard;

use App\Models\User;
use App\Services\Dashboard\DashboardService;

class GetDashboardSummaryAction
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function __invoke(User $user, array $filters): array
    {
        return $this->dashboardService->summary($user, $filters['date_from'] ?? null, $filters['date_to'] ?? null);
    }
}
