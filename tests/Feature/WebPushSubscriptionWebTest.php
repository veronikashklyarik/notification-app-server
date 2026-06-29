<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPushSubscriptionWebTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------------------
    // POST /push-subscriptions/subscribe (web session auth)
    // ---------------------------------------------------------------------------

    public function test_authenticated_user_can_subscribe_via_web_route(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('push-subscriptions.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/web-test-endpoint',
            'keys' => [
                'p256dh' => 'test-p256dh-key',
                'auth' => 'test-auth-key',
            ],
        ]);

        $response->assertCreated()
            ->assertJson(['message' => 'Push subscription registered successfully.']);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/web-test-endpoint',
        ]);
    }

    public function test_subscribe_requires_authentication(): void
    {
        $this->postJson(route('push-subscriptions.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
            'keys' => ['p256dh' => 'key', 'auth' => 'auth'],
        ])->assertUnauthorized();
    }

    public function test_subscribe_rejects_non_push_service_endpoint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('push-subscriptions.subscribe'), [
            'endpoint' => 'https://evil.example.com/push/token',
            'keys' => ['p256dh' => 'key', 'auth' => 'auth'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    }

    // ---------------------------------------------------------------------------
    // DELETE /push-subscriptions/unsubscribe (web session auth)
    // ---------------------------------------------------------------------------

    public function test_authenticated_user_can_unsubscribe_via_web_route(): void
    {
        $user = User::factory()->create();

        PushSubscription::factory()->create([
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/web-test-endpoint',
        ]);

        $response = $this->actingAs($user)->deleteJson(route('push-subscriptions.unsubscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/web-test-endpoint',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Push subscription removed successfully.']);

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_unsubscribe_requires_authentication(): void
    {
        $this->deleteJson(route('push-subscriptions.unsubscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
        ])->assertUnauthorized();
    }
}
