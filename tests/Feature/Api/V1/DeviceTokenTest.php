<?php

namespace Tests\Feature\Api\V1;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_device_token(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/device-tokens', [
            'token' => 'fcm-token-abc123',
            'platform' => 'ios',
            'device_name' => 'iPhone 15',
        ]);

        $response->assertCreated()
            ->assertJson(['message' => 'Device token registered successfully.']);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-token-abc123',
            'platform' => 'ios',
        ]);
    }

    public function test_existing_token_is_updated_on_upsert(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        DeviceToken::factory()->create([
            'user_id' => $user1->id,
            'token' => 'shared-token',
            'platform' => 'android',
        ]);

        Sanctum::actingAs($user2);

        $this->postJson('/api/v1/device-tokens', [
            'token' => 'shared-token',
            'platform' => 'ios',
            'device_name' => 'New Device',
        ]);

        $this->assertDatabaseCount('device_tokens', 1);
        $this->assertDatabaseHas('device_tokens', [
            'token' => 'shared-token',
            'user_id' => $user2->id,
            'platform' => 'ios',
        ]);
    }

    public function test_user_can_delete_device_token(): void
    {
        $user = User::factory()->create();

        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'fcm-token-to-delete',
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/device-tokens', [
            'token' => 'fcm-token-to-delete',
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('device_tokens', 0);
    }

    public function test_user_cannot_delete_another_users_token(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        DeviceToken::factory()->create([
            'user_id' => $user1->id,
            'token' => 'user1-token',
        ]);

        Sanctum::actingAs($user2);

        $this->deleteJson('/api/v1/device-tokens', [
            'token' => 'user1-token',
        ]);

        $this->assertDatabaseCount('device_tokens', 1);
    }

    public function test_store_requires_token_and_platform(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/device-tokens', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token', 'platform']);
    }

    public function test_platform_must_be_ios_or_android(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/device-tokens', [
            'token' => 'some-token',
            'platform' => 'windows',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('platform');
    }
}
