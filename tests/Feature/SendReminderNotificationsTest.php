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

    private function pendingNotifiedEvent(User $user, int $intervalMinutes, int $notifiedMinutesAgo, ?int $remindedMinutesAgo = null): NotificationEvent
    {
        return NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'notification_id' => Notification::factory()->create([
                'user_id' => $user->id,
                'reminder_interval' => $intervalMinutes,
            ]),
            'status' => EventStatus::Pending,
            'scheduled_at' => now()->subHour(),
            'notified_at' => now()->subMinutes($notifiedMinutesAgo),
            'reminded_at' => $remindedMinutesAgo !== null ? now()->subMinutes($remindedMinutesAgo) : null,
        ]);
    }

    public function test_sends_reminder_when_initial_notification_is_old_enough(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);
        $event = $this->pendingNotifiedEvent($user, intervalMinutes: 60, notifiedMinutesAgo: 65);

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        $event->refresh();
        $this->assertNotNull($event->reminded_at);
        Queue::assertPushed(SendPushNotificationJob::class);
    }

    public function test_sends_reminder_when_enough_time_passed_since_last_reminder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);
        $event = $this->pendingNotifiedEvent($user, intervalMinutes: 60, notifiedMinutesAgo: 120, remindedMinutesAgo: 65);

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        $event->refresh();
        Queue::assertPushed(SendPushNotificationJob::class);
    }

    public function test_does_not_send_reminder_too_early_after_initial_notification(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);
        $this->pendingNotifiedEvent($user, intervalMinutes: 60, notifiedMinutesAgo: 30);

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_does_not_send_reminder_too_early_after_last_reminder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);
        $this->pendingNotifiedEvent($user, intervalMinutes: 60, notifiedMinutesAgo: 120, remindedMinutesAgo: 30);

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_does_not_send_reminder_for_non_pending_events(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        foreach ([EventStatus::Done, EventStatus::Cancelled, EventStatus::Postponed] as $status) {
            NotificationEvent::factory()->create([
                'user_id' => $user->id,
                'notification_id' => Notification::factory()->create([
                    'user_id' => $user->id,
                    'reminder_interval' => 60,
                ]),
                'status' => $status,
                'scheduled_at' => now()->subHour(),
                'notified_at' => now()->subHours(2),
                'completed_at' => now()->subHour(),
            ]);
        }

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_does_not_send_reminder_when_notification_has_no_reminder_interval(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'notification_id' => Notification::factory()->create([
                'user_id' => $user->id,
                'reminder_interval' => null,
            ]),
            'status' => EventStatus::Pending,
            'scheduled_at' => now()->subHour(),
            'notified_at' => now()->subHours(2),
        ]);

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_does_not_send_reminder_for_event_never_notified(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'notification_id' => Notification::factory()->create([
                'user_id' => $user->id,
                'reminder_interval' => 60,
            ]),
            'status' => EventStatus::Pending,
            'scheduled_at' => now()->subHour(),
            'notified_at' => null,
        ]);

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_does_not_send_reminder_for_trashed_notification(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'reminder_interval' => 60,
        ]);
        $event = NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $notification->id,
            'status' => EventStatus::Pending,
            'scheduled_at' => now()->subHour(),
            'notified_at' => now()->subHours(2),
        ]);
        $notification->delete();

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        Queue::assertNothingPushed();
        // reminded_at must NOT be stamped since no push was sent
        $this->assertNull($event->fresh()->reminded_at);
    }

    public function test_does_not_dispatch_jobs_when_user_has_no_push_subscriptions(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        // No PushSubscription created
        $event = $this->pendingNotifiedEvent($user, intervalMinutes: 60, notifiedMinutesAgo: 65);

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        Queue::assertNothingPushed();
        // reminded_at still stamped because the notification exists and push loop is simply empty
        $this->assertNotNull($event->fresh()->reminded_at);
    }

    public function test_sends_reminder_when_exactly_at_interval_boundary(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);
        // Exactly 60 minutes ago — diffInMinutes returns 60 which is NOT < 60
        $this->pendingNotifiedEvent($user, intervalMinutes: 60, notifiedMinutesAgo: 60);

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        Queue::assertPushed(SendPushNotificationJob::class);
    }

    public function test_different_notifications_can_have_different_reminder_intervals(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        // 15-minute interval, notified 20 minutes ago — should send
        $eventSoon = $this->pendingNotifiedEvent($user, intervalMinutes: 15, notifiedMinutesAgo: 20);

        // 60-minute interval, notified 20 minutes ago — should NOT send
        $eventLater = $this->pendingNotifiedEvent($user, intervalMinutes: 60, notifiedMinutesAgo: 20);

        $this->artisan('app:send-reminder-notifications')->assertSuccessful();

        $this->assertNotNull($eventSoon->fresh()->reminded_at);
        $this->assertNull($eventLater->fresh()->reminded_at);
        Queue::assertPushed(SendPushNotificationJob::class, 1);
    }
}
