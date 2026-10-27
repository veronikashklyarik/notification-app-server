<?php

namespace Tests\Feature;

use App\Enums\Locale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_landing_without_redirect(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('landing');
        $response->assertSee(config('app.name'));
        $response->assertSee('Reminders you actually mark done.');
    }

    public function test_landing_explains_google_data_and_links_legal_pages(): void
    {
        $response = $this->get(route('landing'));

        $response->assertSee('Notifyr can&#039;t read your Gmail, Calendar, Drive, Contacts or any other Google data.', false);
        $response->assertSee(route('legal.privacy'));
        $response->assertSee(route('legal.terms'));
        $response->assertSee(route('auth.google'));
        $response->assertSee('mailto:'.config('mail.from.address'), false);
    }

    public function test_landing_has_meta_description_and_open_graph(): void
    {
        $response = $this->get(route('landing'));

        $response->assertSee('<meta name="description"', false);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<link rel="canonical" href="'.route('landing').'">', false);
    }

    public function test_guest_gets_english_by_default(): void
    {
        $response = $this->get(route('landing'));

        $response->assertSee('<html lang="en">', false);
        $response->assertSee(route('locale.switch', Locale::Pl));
        $response->assertSee(route('locale.switch', Locale::Ru));
        $response->assertDontSee(route('locale.switch', Locale::En));
    }

    public function test_authenticated_user_is_redirected_to_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('home'));
    }

    public function test_guest_can_switch_landing_language(): void
    {
        $response = $this->from(route('landing'))->get(route('locale.switch', Locale::Ru));

        $response->assertRedirect(route('landing'));
        $response->assertCookie('app_locale', Locale::Ru->value);
        $response->assertSessionHas('locale', Locale::Ru->value);

        $this->get(route('landing'))
            ->assertSee('<html lang="ru">', false)
            ->assertSee(__('landing.hero_title', [], Locale::Ru->value));
    }

    public function test_switching_to_unknown_language_is_not_found(): void
    {
        $this->get('/locale/fr')->assertNotFound();
    }
}
