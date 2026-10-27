<?php

namespace Tests\Feature;

use App\Enums\Locale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_is_public_and_has_google_user_data_tab(): void
    {
        $response = $this->get(route('legal.privacy'));

        $response->assertOk();
        $response->assertSee('id="google"', false);
        $response->assertSee('href="#google"', false);
        $response->assertSee('including the Limited Use requirements');
        $response->assertSee('openid, email and profile');
        $response->assertSee('mailto:'.config('mail.from.address'), false);
        $response->assertSee(route('legal.terms'));
    }

    public function test_privacy_policy_names_the_services_that_process_data(): void
    {
        $response = $this->get(route('legal.privacy'));

        $response->assertSee('DigitalOcean');
        $response->assertSee('Frankfurt, Germany');
        $response->assertSee('Mailgun (EU region)');
        $response->assertSee('Bunny Fonts');
        $response->assertSee('30 to 60 days after deletion');
    }

    public function test_privacy_policy_has_one_chip_per_tab_with_the_first_one_active(): void
    {
        $response = $this->get(route('legal.privacy'));

        foreach (['intro', 'collect', 'use', 'google', 'services', 'storage', 'cookies', 'rights', 'contact'] as $tab) {
            $response->assertSee('href="#'.$tab.'"', false);
            $response->assertSee('id="'.$tab.'"', false);
        }

        $response->assertSeeInOrder(['href="#intro"', 'data-active', 'href="#collect"'], false);
    }

    public function test_terms_are_public_and_have_reminders_disclaimer(): void
    {
        $response = $this->get(route('legal.terms'));

        $response->assertOk();
        $response->assertSee('id="reminders"', false);
        $response->assertSee('Reminders and notifications');
        $response->assertSee('href="#content"', false);
        $response->assertSee(route('legal.privacy'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function localizedPages(): array
    {
        $pages = [];

        foreach (Locale::cases() as $locale) {
            $pages["privacy {$locale->value}"] = ['legal.privacy', $locale->value];
            $pages["terms {$locale->value}"] = ['legal.terms', $locale->value];
        }

        return $pages;
    }

    #[DataProvider('localizedPages')]
    public function test_every_translation_key_is_filled(string $routeName, string $locale): void
    {
        $this->withCookie('app_locale', $locale)
            ->get(route($routeName))
            ->assertOk()
            ->assertDontSee('legal.');
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
