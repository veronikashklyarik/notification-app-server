<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_is_public_and_has_google_user_data_section(): void
    {
        $response = $this->get(route('legal.privacy'));

        $response->assertOk();
        $response->assertSee('id="google"', false);
        $response->assertSee('Google API Services User Data Policy');
        $response->assertSee('openid, email, profile');
        $response->assertSee('mailto:'.config('mail.from.address'), false);
        $response->assertSee(route('legal.terms'));
    }

    public function test_terms_are_public_and_have_reminders_disclaimer(): void
    {
        $response = $this->get(route('legal.terms'));

        $response->assertOk();
        $response->assertSee('id="reminders"', false);
        $response->assertSee('Reminders and notifications');
        $response->assertSee(route('legal.privacy'));
    }

    public function test_guest_goes_back_to_landing_not_login(): void
    {
        $response = $this->get(route('legal.privacy'));

        $response->assertSee('href="'.route('landing').'"', false);
        $response->assertDontSee('href="'.route('login').'"', false);
    }

    public function test_signed_in_user_goes_back_to_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('legal.terms'))
            ->assertSee('href="'.route('settings').'"', false);
    }
}
