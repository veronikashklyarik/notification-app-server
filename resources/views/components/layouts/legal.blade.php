<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }} — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas antialiased min-h-screen" style="padding-top: env(safe-area-inset-top);">

    <main class="max-w-2xl mx-auto px-5 pt-5 pb-10" style="padding-bottom: calc(env(safe-area-inset-bottom) + 3rem);">
        <a href="{{ auth()->check() ? route('settings') : route('login') }}" class="text-[15px] font-semibold text-text-secondary">‹ {{ $back ?? (auth()->check() ? __('Settings') : __('Sign in')) }}</a>

        {{ $slot }}

        <footer class="mt-10 pt-6 border-t border-border-light text-center">
            <p class="text-[12px] text-text-quaternary">&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
        </footer>
    </main>

</body>
</html>
