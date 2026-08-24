<?php

namespace App\Actions\Notification;

use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;

class ListNotificationsAction
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function __invoke(User $user, array $filters): LengthAwarePaginator
    {
        return $this->notificationService->list($user, $filters);
    }
}
