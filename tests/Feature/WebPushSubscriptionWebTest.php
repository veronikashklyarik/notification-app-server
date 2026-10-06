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
    // Settings page integration
    // ---------------------------------------------------------------------------

    public function test_settings_page_shows_push_notification_settings_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings'))
            ->assertOk()
            ->assertSee('Push on this device');
    }

    public function test_settings_page_does_not_show_push_notification_settings_for_guests(): void
    {
        $this->get(route('settings'))
            ->assertRedirect(route('login'));
    }

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

    // ---------------------------------------------------------------------------
    // GET /push-subscriptions/status (web session auth)
    // ---------------------------------------------------------------------------

    public function test_status_reports_true_when_the_endpoint_belongs_to_the_current_user(): void
    {
        $user = User::factory()->create();

        PushSubscription::factory()->create([
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/shared-device',
        ]);

        $this->actingAs($user)
            ->getJson(route('push-subscriptions.status', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/shared-device']))
            ->assertOk()
            ->assertJson(['subscribed' => true]);
    }

    public function test_status_reports_false_when_the_endpoint_belongs_to_a_different_user_on_the_same_device(): void
    {
        $otherUser = User::factory()->create();
        $currentUser = User::factory()->create();

        PushSubscription::factory()->create([
            'user_id' => $otherUser->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/shared-device',
        ]);

        $this->actingAs($currentUser)
            ->getJson(route('push-subscriptions.status', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/shared-device']))
            ->assertOk()
            ->assertJson(['subscribed' => false]);
    }

    public function test_status_reports_false_when_the_endpoint_is_unknown(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('push-subscriptions.status', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/never-seen']))
            ->assertOk()
            ->assertJson(['subscribed' => false]);
    }

    public function test_status_requires_authentication(): void
    {
        $this->getJson(route('push-subscriptions.status', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/test']))
            ->assertUnauthorized();
    }
}
