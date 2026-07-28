<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): SymfonyRedirectResponse
    {
        if ($request->filled('timezone')) {
            $request->session()->put('google_timezone', $request->string('timezone')->toString());
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors([
                'email' => __('Google authentication failed. Please try again.'),
            ]);
        }

        $user = User::query()->where('google_id', $googleUser->getId())->first()
            ?? User::query()->where('email', $googleUser->getEmail())->first();

        if ($user) {
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        } else {
            $timezone = $request->session()->pull('google_timezone', 'UTC');
            $locale = $request->cookie('app_locale')
                ?? $request->session()->get('locale')
                ?? config('app.locale', 'en');

            $user = User::query()->create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'timezone' => $timezone,
                'locale' => $locale,
            ]);

            $user->markEmailAsVerified();

            event(new Registered($user));
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('home'));
    }
}
