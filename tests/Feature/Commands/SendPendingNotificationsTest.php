<?php

namespace Tests\Feature\Commands;

use App\Enums\EventStatus;
use App\Jobs\SendPushNotificationJob;
use App\Models\NotificationEvent;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendPendingNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_events_set_notified_at_and_stay_pending(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $subscription = PushSubscription::factory()->create(['user_id' => $user->id]);

        $event = NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'scheduled_at' => now()->subMinute(),
            'status' => EventStatus::Pending,
        ]);

        $this->artisan('app:send-pending-notifications')->assertSuccessful();

        // Status stays Pending — user must act to complete it
        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Pending->value,
        ]);

        // notified_at is stamped so cron won't resend
        $this->assertNotNull($event->fresh()->notified_at);

        Queue::assertPushed(SendPushNotificationJob::class, function ($job) use ($subscription) {
            return $job->subscription->id === $subscription->id;
        });
    }

    public function test_job_receives_event_detail_url(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        $event = NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'scheduled_at' => now()->subMinute(),
            'status' => EventStatus::Pending,
        ]);

        $this->artisan('app:send-pending-notifications')->assertSuccessful();

        Queue::assertPushed(SendPushNotificationJob::class, function ($job) use ($event) {
            return $job->options['url'] === route('events.show', $event);
        });
    }

    public function test_already_notified_events_are_not_redispatched(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'scheduled_at' => now()->subMinute(),
            'status' => EventStatus::Pending,
            'notified_at' => now()->subSeconds(30),
        ]);

        $this->artisan('app:send-pending-notifications')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_future_events_are_not_dispatched(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        $event = NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'scheduled_at' => now()->addHour(),
            'status' => EventStatus::Pending,
        ]);

        $this->artisan('app:send-pending-notifications')->assertSuccessful();

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Pending->value,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_orphaned_event_sets_notified_at_without_dispatching(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        $event = NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'scheduled_at' => now()->subMinute(),
            'status' => EventStatus::Pending,
        ]);
        $event->notification->delete();

        $this->artisan('app:send-pending-notifications')->assertSuccessful();

        $this->assertNotNull($event->fresh()->notified_at);
        Queue::assertNothingPushed();
    }

    public function test_already_done_events_are_not_redispatched(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        NotificationEvent::factory()->done()->create([
            'user_id' => $user->id,
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('app:send-pending-notifications')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_event_with_no_push_subscriptions_sets_notified_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $event = NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'scheduled_at' => now()->subMinute(),
            'status' => EventStatus::Pending,
        ]);

        $this->artisan('app:send-pending-notifications')->assertSuccessful();

        $this->assertNotNull($event->fresh()->notified_at);
        // Status stays Pending — user still needs to act
        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Pending->value,
        ]);

        Queue::assertNothingPushed();
    }
}
