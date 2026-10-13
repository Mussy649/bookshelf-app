<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class NotificationPolicy
{
    /**
     * Determine whether the user can mark the notification as read.
     */
    public function markAsRead(
        User $user,
        DatabaseNotification $notification
    ): bool {
        return $notification->notifiable_type === User::class
            && (int) $notification->notifiable_id === $user->id;
    }
}
