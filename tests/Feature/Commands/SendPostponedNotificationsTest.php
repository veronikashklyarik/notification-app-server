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

class SendPostponedNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_snoozed_events_go_back_to_pending_and_resend_a_push(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $subscription = PushSubscription::factory()->create(['user_id' => $user->id]);

        $event = NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'scheduled_at' => now()->subMinutes(20),
            'status' => EventStatus::Postponed,
            'postponed_until' => now()->subMinute(),
            'notified_at' => now()->subMinutes(20),
        ]);

        $this->artisan('app:send-postponed-notifications')->assertSuccessful();

        $event->refresh();
        $this->assertSame(EventStatus::Pending, $event->status);
        $this->assertNull($event->postponed_until);
        $this->assertTrue($event->notified_at->greaterThan(now()->subMinute()));

        Queue::assertPushed(SendPushNotificationJob::class, function ($job) use ($subscription) {
            return $job->subscription->id === $subscription->id;
        });
    }

    public function test_not_yet_due_snoozed_events_are_left_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        $event = NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'scheduled_at' => now()->subMinutes(20),
            'status' => EventStatus::Postponed,
            'postponed_until' => now()->addMinutes(10),
        ]);

        $this->artisan('app:send-postponed-notifications')->assertSuccessful();

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Postponed->value,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_orphaned_postponed_event_still_returns_to_pending_without_dispatching(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        $event = NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'scheduled_at' => now()->subMinutes(20),
            'status' => EventStatus::Postponed,
            'postponed_until' => now()->subMinute(),
        ]);
        $event->notification->delete();

        $this->artisan('app:send-postponed-notifications')->assertSuccessful();

        $this->assertSame(EventStatus::Pending, $event->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_non_postponed_events_are_not_touched(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        $event = NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'scheduled_at' => now()->subMinutes(20),
            'status' => EventStatus::Pending,
        ]);

        $this->artisan('app:send-postponed-notifications')->assertSuccessful();

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Pending->value,
        ]);

        Queue::assertNothingPushed();
    }
}
