<?php

namespace App\Actions\Notification;

use App\Models\User;
use App\Services\Notification\NotificationService;

class MarkAllNotificationsReadAction
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function __invoke(User $user): void
    {
        $this->notificationService->markAllAsRead($user);
    }
}
