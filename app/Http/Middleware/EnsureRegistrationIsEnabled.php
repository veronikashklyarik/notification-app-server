<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationIsEnabled
{
    /**
     * Hide the sign-up endpoints while registration is closed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('auth.registration_enabled'), 404);

        return $next($request);
    }
}
