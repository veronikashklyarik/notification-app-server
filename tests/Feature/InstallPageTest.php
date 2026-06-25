<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('install'))->assertRedirect(route('login'));
    }

    public function test_unverified_users_are_redirected_to_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('install'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_users_can_view_install_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('install'))
            ->assertOk()
            ->assertViewIs('install');
    }
}
