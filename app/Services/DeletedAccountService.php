<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\RestoreMethod;
use App\Models\User;
use App\Notifications\AccountRestoredNotification;
use Illuminate\Support\Facades\DB;

class DeletedAccountService
{
    public function __construct(private NotificationEventService $eventService) {}

    /**
     * A deleted account that can still be restored by signing in. One past the restore window is
     * erased right away, so its email (or Google account) is free for a new sign-up.
     *
     * @param  'email'|'google_id'  $column
     */
    public function findRestorable(string $column, string $value): ?User
    {
        $user = User::onlyTrashed()->where($column, $value)->first();

        if ($user === null) {
            return null;
        }

        if ($user->deleted_at->lte(now()->subDays(User::RESTORE_DAYS))) {
            $user->prune();

            return null;
        }

        return $user;
    }

    /**
     * Bring the account back with the reminders that were deleted together with it, and tell the
     * owner by email so a restore they didn't make doesn't go unnoticed.
     */
    public function restore(User $user, RestoreMethod $method): void
    {
        $deletedAt = $user->deleted_at;

        DB::transaction(function () use ($user, $deletedAt): void {
            $user->restore();

            // Occurrences that fell while the account was gone were never reminded; without this they'd flood Catch up.
            $user->notificationEvents()
                ->where('status', EventStatus::Pending)
                ->whereBetween('scheduled_at', [$deletedAt, now()])
                ->delete();

            $user->reminders()->onlyTrashed()->where('deleted_at', '>=', $deletedAt)->restore();
        });

        foreach ($user->reminders()->get() as $reminder) {
            $this->eventService->topUpEvents($reminder);
        }

        $user->notify(new AccountRestoredNotification($method, now()));
    }
}
