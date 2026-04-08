<?php

namespace App\Providers;

use App\Models\NotificationEvent;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        VerifyEmail::createUrlUsing(function (mixed $notifiable): string {
            return URL::temporarySignedRoute(
                'api.v1.auth.email.verify',
                now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });

        RateLimiter::for('api-auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        Route::model('event', NotificationEvent::class);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->info->title = 'Notifyr API';
                $openApi->secure(SecurityScheme::http('bearer'));
            })
            ->routes(fn (RoutingRoute $route) => str_starts_with($route->uri, 'api/v1'));
    }
}
