<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendPushNotificationJob;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendPushNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_is_deleted_on_410_response(): void
    {
        $subscription = PushSubscription::factory()->create();

        $webPush = $this->createMock(WebPushService::class);
        $webPush->method('send')->willReturn(410);
        $this->app->instance(WebPushService::class, $webPush);

        $job = new SendPushNotificationJob($subscription, 'Title', 'Body');
        $job->handle($webPush);

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_subscription_is_deleted_on_404_response(): void
    {
        $subscription = PushSubscription::factory()->create();

        $webPush = $this->createMock(WebPushService::class);
        $webPush->method('send')->willReturn(404);
        $this->app->instance(WebPushService::class, $webPush);

        $job = new SendPushNotificationJob($subscription, 'Title', 'Body');
        $job->handle($webPush);

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_exception_is_thrown_on_null_response_to_allow_retry(): void
    {
        $subscription = PushSubscription::factory()->create();

        $webPush = $this->createMock(WebPushService::class);
        $webPush->method('send')->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $job = new SendPushNotificationJob($subscription, 'Title', 'Body');
        $job->handle($webPush);
    }

    public function test_exception_is_thrown_on_5xx_response_to_allow_retry(): void
    {
        $subscription = PushSubscription::factory()->create();

        $webPush = $this->createMock(WebPushService::class);
        $webPush->method('send')->willReturn(500);

        $this->expectException(\RuntimeException::class);

        $job = new SendPushNotificationJob($subscription, 'Title', 'Body');
        $job->handle($webPush);
    }

    public function test_job_returns_early_if_subscription_already_deleted(): void
    {
        $subscription = PushSubscription::factory()->create();
        $subscription->delete();

        $webPush = $this->createMock(WebPushService::class);
        $webPush->expects($this->never())->method('send');

        $job = new SendPushNotificationJob($subscription, 'Title', 'Body');
        $job->handle($webPush);
    }

    public function test_job_completes_successfully_on_2xx_response(): void
    {
        $subscription = PushSubscription::factory()->create();

        $webPush = $this->createMock(WebPushService::class);
        $webPush->method('send')->willReturn(201);

        $job = new SendPushNotificationJob($subscription, 'Title', 'Body');
        $job->handle($webPush);

        // No exception thrown, subscription not deleted
        $this->assertDatabaseCount('push_subscriptions', 1);
    }
}
