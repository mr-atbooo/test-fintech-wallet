<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Actions\Dashboard\GetDashboardChartAction;
use App\Actions\Dashboard\GetDashboardSummaryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Dashboard\ChartRequest;
use App\Http\Requests\Api\V1\Dashboard\SummaryRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function summary(SummaryRequest $request, GetDashboardSummaryAction $action): JsonResponse
    {
        $summary = $action($request->user(), $request->validated());

        return ApiResponse::success($summary, __('api.dashboard.summary_fetched'));
    }

    public function chart(ChartRequest $request, GetDashboardChartAction $action): JsonResponse
    {
        $chart = $action($request->user(), $request->validated());

        return ApiResponse::success($chart, __('api.dashboard.chart_fetched'));
    }
}
