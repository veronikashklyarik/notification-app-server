<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_refresh_with_valid_refresh_token(): void
    {
        $user = User::factory()->create();

        $refreshToken = $user->createToken('device-refresh', ['refresh'], now()->addDays(30));

        $response = $this->withToken($refreshToken->plainTextToken)
            ->postJson('/api/v1/auth/refresh');

        $response->assertOk()
            ->assertJsonStructure(['token', 'refresh_token']);
    }

    public function test_cannot_refresh_with_access_token(): void
    {
        $user = User::factory()->create();

        $accessToken = $user->createToken('device', ['*'], now()->addDays(7));

        $response = $this->withToken($accessToken->plainTextToken)
            ->postJson('/api/v1/auth/refresh');

        $response->assertForbidden();
    }

    public function test_cannot_refresh_without_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh');

        $response->assertUnauthorized();
    }

    public function test_old_refresh_token_is_revoked_after_use(): void
    {
        $user = User::factory()->create();

        $refreshToken = $user->createToken('device-refresh', ['refresh'], now()->addDays(30));
        $tokenString = $refreshToken->plainTextToken;

        $this->withToken($tokenString)
            ->postJson('/api/v1/auth/refresh')
            ->assertOk();

        // Verify the token record was deleted from the database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $refreshToken->accessToken->id,
        ]);
    }
}
