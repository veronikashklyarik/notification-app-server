<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Jobs\SendPushNotificationJob;
use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendReminderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithReminder(int $intervalMinutes): User
    {
        return User::factory()->create(['reminder_interval' => $intervalMinutes]);
    }

    private function pendingNotifiedEvent(User $user, int $notifiedMinutesAgo, ?int $remindedMinutesAgo = null): NotificationEvent
    {
        return NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'notification_id' => Notification::factory()->create(['user_id' => $user->id]),
            'status' => EventStatus::Pending,
            'scheduled_at' => now()->subHour(),
            'notified_at' => now()->subMinutes($notifiedMinutesAgo),
            'reminded_at' => $remindedMinutesAgo !== null ? now()->subMinutes($remindedMinutesAgo) : null,
        ]);
    }

    public function test_sends_reminder_when_initial_notification_is_old_enough(): void
    {
        Queue::fake();

        $user = $this->userWithReminder(60);
        PushSubscription::factory()->create(['user_id' => $user->id]);
        $event = $this->pendingNotifiedEvent($user, notifiedMinutesAgo: 65);

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        $event->refresh();
        $this->assertNotNull($event->reminded_at);
        Queue::assertPushed(SendPushNotificationJob::class);
    }

    public function test_sends_reminder_when_enough_time_passed_since_last_reminder(): void
    {
        Queue::fake();

        $user = $this->userWithReminder(60);
        PushSubscription::factory()->create(['user_id' => $user->id]);
        $event = $this->pendingNotifiedEvent($user, notifiedMinutesAgo: 120, remindedMinutesAgo: 65);

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        $event->refresh();
        Queue::assertPushed(SendPushNotificationJob::class);
    }

    public function test_does_not_send_reminder_too_early_after_initial_notification(): void
    {
        Queue::fake();

        $user = $this->userWithReminder(60);
        PushSubscription::factory()->create(['user_id' => $user->id]);
        $this->pendingNotifiedEvent($user, notifiedMinutesAgo: 30);

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_does_not_send_reminder_too_early_after_last_reminder(): void
    {
        Queue::fake();

        $user = $this->userWithReminder(60);
        PushSubscription::factory()->create(['user_id' => $user->id]);
        $this->pendingNotifiedEvent($user, notifiedMinutesAgo: 120, remindedMinutesAgo: 30);

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_does_not_send_reminder_for_non_pending_events(): void
    {
        Queue::fake();

        $user = $this->userWithReminder(60);
        PushSubscription::factory()->create(['user_id' => $user->id]);

        foreach ([EventStatus::Done, EventStatus::Cancelled, EventStatus::Postponed] as $status) {
            NotificationEvent::factory()->create([
                'user_id' => $user->id,
                'notification_id' => Notification::factory()->create(['user_id' => $user->id]),
                'status' => $status,
                'scheduled_at' => now()->subHour(),
                'notified_at' => now()->subHours(2),
                'completed_at' => now()->subHour(),
            ]);
        }

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_does_not_send_reminder_when_user_has_no_reminder_interval(): void
    {
        Queue::fake();

        $user = User::factory()->create(['reminder_interval' => null]);
        PushSubscription::factory()->create(['user_id' => $user->id]);
        $this->pendingNotifiedEvent($user, notifiedMinutesAgo: 120);

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_does_not_send_reminder_for_event_never_notified(): void
    {
        Queue::fake();

        $user = $this->userWithReminder(60);
        PushSubscription::factory()->create(['user_id' => $user->id]);

        NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'notification_id' => Notification::factory()->create(['user_id' => $user->id]),
            'status' => EventStatus::Pending,
            'scheduled_at' => now()->subHour(),
            'notified_at' => null,
        ]);

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        Queue::assertNothingPushed();
    }
}
