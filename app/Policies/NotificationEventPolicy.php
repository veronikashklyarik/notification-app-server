<?php

namespace App\Policies;

use App\Models\NotificationEvent;
use App\Models\User;

class NotificationEventPolicy
{
    public function view(User $user, NotificationEvent $notificationEvent): bool
    {
        return $user->id === $notificationEvent->user_id;
    }

    public function update(User $user, NotificationEvent $notificationEvent): bool
    {
        return $user->id === $notificationEvent->user_id;
    }
}
