<?php

namespace Tests\Feature\Api\V1;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------------------
    // VAPID public key endpoint
    // ---------------------------------------------------------------------------

    public function test_vapid_public_key_is_returned_without_auth(): void
    {
        config(['services.vapid.public_key' => 'test-public-key']);

        $response = $this->getJson('/api/v1/push-subscriptions/vapid-public-key');

        $response->assertOk()
            ->assertJson(['public_key' => 'test-public-key']);
    }

    public function test_vapid_public_key_returns_503_when_not_configured(): void
    {
        config(['services.vapid.public_key' => null]);

        $response = $this->getJson('/api/v1/push-subscriptions/vapid-public-key');

        $response->assertServiceUnavailable();
    }

    // ---------------------------------------------------------------------------
    // POST /push-subscriptions
    // ---------------------------------------------------------------------------

    public function test_user_can_register_push_subscription(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'keys' => [
                'p256dh' => 'test-p256dh-key',
                'auth' => 'test-auth-key',
            ],
        ]);

        $response->assertCreated()
            ->assertJson(['message' => 'Push subscription registered successfully.']);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'p256dh' => 'test-p256dh-key',
            'auth' => 'test-auth-key',
        ]);
    }

    public function test_same_endpoint_from_different_user_creates_separate_subscription(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        PushSubscription::factory()->create([
            'user_id' => $user1->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/shared-endpoint',
        ]);

        Sanctum::actingAs($user2);

        $this->postJson('/api/v1/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/shared-endpoint',
            'keys' => ['p256dh' => 'new-key', 'auth' => 'new-auth'],
        ])->assertCreated();

        // Both users have their own subscription — user1's is not overwritten
        $this->assertDatabaseCount('push_subscriptions', 2);
        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/shared-endpoint',
            'user_id' => $user1->id,
        ]);
        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/shared-endpoint',
            'user_id' => $user2->id,
        ]);
    }

    public function test_existing_subscription_keys_are_updated_for_same_user(): void
    {
        $user = User::factory()->create();

        PushSubscription::factory()->create([
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/my-endpoint',
            'p256dh' => 'old-p256dh',
            'auth' => 'old-auth',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/my-endpoint',
            'keys' => ['p256dh' => 'new-p256dh', 'auth' => 'new-auth'],
        ])->assertCreated();

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/my-endpoint',
            'p256dh' => 'new-p256dh',
            'auth' => 'new-auth',
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'keys' => ['p256dh' => 'key', 'auth' => 'auth'],
        ])->assertUnauthorized();
    }

    public function test_store_rejects_invalid_endpoint(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/push-subscriptions', [
            'endpoint' => 'not-a-url',
            'keys' => ['p256dh' => 'key', 'auth' => 'auth'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    }

    public function test_store_rejects_non_push_service_endpoint(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/push-subscriptions', [
            'endpoint' => 'https://evil.example.com/push/token',
            'keys' => ['p256dh' => 'key', 'auth' => 'auth'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    }

    public function test_store_rejects_missing_keys(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['keys.p256dh', 'keys.auth']);
    }

    // ---------------------------------------------------------------------------
    // DELETE /push-subscriptions
    // ---------------------------------------------------------------------------

    public function test_user_can_delete_push_subscription(): void
    {
        $user = User::factory()->create();

        PushSubscription::factory()->create([
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/my-endpoint',
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/my-endpoint',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Push subscription removed successfully.']);

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_user_cannot_delete_another_users_subscription(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        PushSubscription::factory()->create([
            'user_id' => $user1->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/user1-endpoint',
        ]);

        Sanctum::actingAs($user2);

        $this->deleteJson('/api/v1/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/user1-endpoint',
        ])->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 1);
    }

    public function test_destroy_requires_authentication(): void
    {
        $this->deleteJson('/api/v1/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
        ])->assertUnauthorized();
    }
}
