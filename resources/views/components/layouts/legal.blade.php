<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Notifyr' }} — Notifyr</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-gray-50 via-white to-indigo-50/30 antialiased min-h-screen"
      style="padding-top: env(safe-area-inset-top);">

    {{-- Sticky top bar --}}
    <header class="sticky top-0 z-10 bg-white border-b border-gray-100"
            style="padding-top: env(safe-area-inset-top);">
        <div class="max-w-2xl mx-auto px-6 h-14 flex items-center">
            <a href="{{ url()->previous(route('login')) }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ __('Back') }}
            </a>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-6 pt-10 pb-10 animate-slide-up" style="padding-bottom: calc(env(safe-area-inset-bottom) + 3rem);">
        {{ $slot }}

        <footer class="mt-16 pt-8 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-300">&copy; {{ date('Y') }} Notifyr. {{ __('All rights reserved.') }}</p>
        </footer>
    </main>

</body>
</html>
