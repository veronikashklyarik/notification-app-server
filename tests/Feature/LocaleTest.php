<?php

namespace Tests\Feature;

use App\Livewire\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_default_english_locale(): void
    {
        $user = User::factory()->create();

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_locale_middleware_sets_english_for_english_user(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)->get(route('home'));

        $this->assertSame('en', app()->getLocale());
    }

    public function test_locale_middleware_sets_russian_for_russian_user(): void
    {
        $user = User::factory()->create(['locale' => 'ru']);

        $this->actingAs($user)->get(route('home'));

        $this->assertSame('ru', app()->getLocale());
    }

    public function test_profile_update_saves_locale(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('locale', 'ru')
            ->call('updateLang');

        $this->assertSame('ru', $user->fresh()->locale);
    }

    public function test_profile_rejects_invalid_locale(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('locale', 'fr')
            ->call('updateLang')
            ->assertHasErrors(['locale']);
    }
}
