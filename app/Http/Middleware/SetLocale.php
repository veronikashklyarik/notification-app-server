<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $locale = ($user ? $user->locale : null)
            ?? $request->cookie('app_locale')
            ?? $request->session()->get('locale')
            ?? config('app.locale', 'en');

        App::setLocale($locale);
        Carbon::setLocale($locale);

        if ($request->user()) {
            $request->session()->put('locale', $locale);
            Cookie::queue('app_locale', $locale, 60 * 24 * 365);
        }

        return $next($request);
    }
}
