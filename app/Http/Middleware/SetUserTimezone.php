<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetUserTimezone
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $timezone = $request->user()?->timezone ?? 'UTC';

        View::share('userTimezone', $timezone);
        Config::set('app.user_timezone', $timezone);

        return $next($request);
    }
}
