<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">

    <title>{{ __("You're offline") }} — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css'])
</head>
<body class="bg-canvas antialiased" style="min-height: 100dvh; padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom);">

    <div class="flex flex-col items-center justify-center px-6 py-10" style="min-height: 100dvh;">
        <div class="w-full max-w-sm">
            <div class="w-14 h-14 rounded-full bg-fill flex items-center justify-center">
                <svg class="w-6 h-6 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8.5 16.5a5 5 0 0 1 7 0M5 13a9 9 0 0 1 14 0M12 20h.01" />
                    <path d="M3 3l18 18" />
                </svg>
            </div>
            <h1 class="mt-3.5 text-[26px] font-bold tracking-title text-ink">{{ __("You're offline") }}</h1>
            <p class="mt-1.5 text-[15px] leading-normal text-text-secondary">{{ __(':appName needs a connection to show and mark your reminders. Notifications already scheduled still arrive.', ['appName' => config('app.name')]) }}</p>
            <button type="button" onclick="location.reload()" class="mt-5 w-full h-[50px] rounded-field bg-ink text-[15px] font-semibold text-white">
                {{ __('Try again') }}
            </button>
        </div>
    </div>

</body>
</html>
