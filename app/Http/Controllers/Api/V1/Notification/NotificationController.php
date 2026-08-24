<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Actions\Notification\ListNotificationsAction;
use App\Actions\Notification\MarkAllNotificationsReadAction;
use App\Actions\Notification\MarkNotificationReadAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notification\IndexNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(IndexNotificationRequest $request, ListNotificationsAction $action): JsonResponse
    {
        $notifications = $action($request->user(), $request->validated());

        return ApiResponse::success(NotificationResource::collection($notifications), __('api.notification.fetched'));
    }

    public function read(Request $request, Notification $notification, MarkNotificationReadAction $action): JsonResponse
    {
        $this->authorize('update', $notification);

        $notification = $action($notification);

        return ApiResponse::success(NotificationResource::make($notification), __('api.notification.marked_read'));
    }

    public function readAll(Request $request, MarkAllNotificationsReadAction $action): JsonResponse
    {
        $action($request->user());

        return ApiResponse::success(null, __('api.notification.all_marked_read'));
    }
}
