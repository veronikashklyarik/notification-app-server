<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_account(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/auth/account', [
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Account deleted successfully.']);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_cannot_delete_account_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/auth/account', [
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_deleting_account_removes_tokens_and_device_tokens(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $user->createToken('device', ['*']);
        DeviceToken::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/auth/account', [
            'password' => 'password123',
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseCount('device_tokens', 0);
    }

    public function test_deleting_account_soft_deletes_notifications(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        Notification::factory()->count(3)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/auth/account', [
            'password' => 'password123',
        ]);

        $this->assertDatabaseCount('notifications', 3);
        $this->assertEquals(0, Notification::query()->where('user_id', $user->id)->count());
    }
}
