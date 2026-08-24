<?php

namespace App\Repositories;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationRepository
{
    public function paginateForUser(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->when(
                ! empty($filters['unread_only']),
                fn ($query) => $query->whereNull('read_at')
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function create(User $user, NotificationType $type, string $title, string $body, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    public function markRead(Notification $notification): Notification
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return $notification;
    }

    public function markAllReadForUser(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
