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

    public function test_due_events_are_marked_done_and_jobs_dispatched(): void
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

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Done->value,
        ]);

        Queue::assertPushed(SendPushNotificationJob::class, function ($job) use ($subscription) {
            return $job->subscription->id === $subscription->id;
        });
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

    public function test_orphaned_event_is_marked_done_without_dispatching(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        // Create event then soft-delete the notification
        $event = NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'scheduled_at' => now()->subMinute(),
            'status' => EventStatus::Pending,
        ]);
        $event->notification->delete();

        $this->artisan('app:send-pending-notifications')->assertSuccessful();

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Done->value,
        ]);

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

    public function test_event_with_no_push_subscriptions_is_still_marked_done(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        // No push subscriptions for this user

        $event = NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'scheduled_at' => now()->subMinute(),
            'status' => EventStatus::Pending,
        ]);

        $this->artisan('app:send-pending-notifications')->assertSuccessful();

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Done->value,
        ]);

        Queue::assertNothingPushed();
    }
}
