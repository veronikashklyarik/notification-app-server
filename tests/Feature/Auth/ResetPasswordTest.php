<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_page_renders(): void
    {
        $this->get(route('password.reset', ['token' => 'a-token']).'?email=user@example.com')
            ->assertOk();
    }

    public function test_a_single_password_field_is_enough_to_reset(): void
    {
        $user = User::factory()->create(['email' => 'veronika@example.com']);
        $token = Password::createToken($user);

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'veronika@example.com',
            'password' => 'newpassword1',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('newpassword1', $user->fresh()->password));
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'veronika@example.com']);

        $response = $this->post(route('password.store'), [
            'token' => 'not-the-real-token',
            'email' => 'veronika@example.com',
            'password' => 'newpassword1',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Hash::check('newpassword1', $user->fresh()->password));
    }

    public function test_password_must_meet_the_minimum_rules(): void
    {
        $user = User::factory()->create(['email' => 'veronika@example.com']);
        $token = Password::createToken($user);

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'veronika@example.com',
            'password' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
