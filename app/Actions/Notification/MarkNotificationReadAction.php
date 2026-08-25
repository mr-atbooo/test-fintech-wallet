<?php

namespace App\Actions\Notification;

use App\Models\Notification;
use App\Services\Notification\NotificationService;

class MarkNotificationReadAction
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function __invoke(Notification $notification): Notification
    {
        return $this->notificationService->markAsRead($notification);
    }
}
