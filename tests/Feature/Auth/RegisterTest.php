<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_is_redirected_to_verification_notice_after_registration(): void
    {
        $this->post(route('register'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    public function test_unverified_user_can_access_home_without_verification(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk();
    }

    public function test_unverified_user_can_access_profile_page(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk();
    }

    public function test_guests_cannot_register_with_invalid_data(): void
    {
        $this->post(route('register'), [])->assertInvalid(['name', 'email', 'password']);
    }

    public function test_guests_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post(route('register'), [
            'name' => 'Other',
            'email' => 'taken@example.com',
            'password' => 'password123',
        ])->assertInvalid(['email']);
    }

    public function test_registration_form_is_hidden_when_registration_is_disabled(): void
    {
        config(['auth.registration_enabled' => false]);

        $this->get(route('register'))->assertNotFound();
    }

    public function test_guests_cannot_register_when_registration_is_disabled(): void
    {
        config(['auth.registration_enabled' => false]);

        $this->post(route('register'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }

    public function test_sign_up_links_are_hidden_when_registration_is_disabled(): void
    {
        config(['auth.registration_enabled' => false]);

        $this->get(route('login'))->assertOk()->assertDontSee(route('register'));
        $this->get(route('landing'))->assertOk()->assertDontSee(route('register'));
    }

    public function test_sign_up_links_are_shown_when_registration_is_enabled(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('register'));
        $this->get(route('landing'))->assertOk()->assertSee(route('register'));
    }
}
