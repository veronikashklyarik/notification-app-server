<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password123',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/auth/password', [
            'current_password' => 'old-password123',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Password changed successfully.']);

        $this->assertTrue(Hash::check('new-password456', $user->fresh()->password));
    }

    public function test_cannot_change_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password123',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/auth/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');
    }

    public function test_password_must_be_confirmed(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password123',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/auth/password', [
            'current_password' => 'old-password123',
            'password' => 'new-password456',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_other_tokens_are_revoked_after_password_change(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password123',
        ]);

        $user->createToken('other-device', ['*']);
        $user->createToken('another-device', ['*']);

        Sanctum::actingAs($user);

        $this->putJson('/api/v1/auth/password', [
            'current_password' => 'old-password123',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ]);

        // Only the current token should remain (Sanctum::actingAs creates a transient token)
        // The two explicitly created tokens should be deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
