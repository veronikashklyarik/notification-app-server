<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ ($title ?? null) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <meta name="theme-color" content="#6366f1">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="apple-touch-startup-image" href="/splash.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-canvas md:bg-canvas mobile-gradient-bg antialiased">

    {{-- Navigation --}}
    <nav class="hidden md:block bg-white border-b border-border sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-8">
            <div class="flex items-center gap-7" style="height: 62px">

                <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0">
                    <div class="w-8 h-8 rounded-control bg-brand flex items-center justify-center">
                        <svg class="w-[18px] h-[18px] text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <span class="text-[17px] font-bold tracking-title text-ink">{{ config('app.name') }}</span>
                </a>

                <div class="flex items-center gap-[22px] text-[14px] font-semibold">
                    @php
                        $desktopNavTabs = [
                            ['route' => 'home', 'pattern' => 'home', 'label' => __('Today')],
                            ['route' => 'notifications.index', 'pattern' => 'notifications.*', 'label' => __('Reminders')],
                            ['route' => 'events.index', 'pattern' => 'events.*', 'label' => __('History')],
                        ];
                    @endphp
                    @foreach($desktopNavTabs as $navTab)
                        @php
                            $navActive = request()->routeIs(...(array) $navTab['pattern']);
                        @endphp
                        <a href="{{ route($navTab['route']) }}"
                           class="h-[62px] flex items-center border-b-2 {{ $navActive ? 'border-brand text-ink' : 'border-transparent text-text-tertiary' }}">
                            {{ $navTab['label'] }}
                        </a>
                    @endforeach
                </div>

                <div class="flex-1"></div>

                <a href="{{ route('notifications.create') }}"
                   class="shrink-0 h-9 px-4 rounded-pill bg-brand text-white text-[14px] font-semibold flex items-center">
                    {{ __('New Reminder') }}
                </a>

                <a href="{{ route('settings') }}" class="shrink-0 w-8 h-8 rounded-full overflow-hidden bg-avatar-bg flex items-center justify-center">
                    @if(auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-[13px] font-semibold text-text-secondary">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    @endif
                </a>

            </div>
        </div>
    </nav>

    {{-- Page Content --}}
    @php
        $hideTabBar = request()->routeIs(['notifications.show', 'notifications.create', 'notifications.edit', 'catch-up', 'install']);
    @endphp
    <main class="max-w-6xl mx-auto px-0 md:px-8 py-0 md:py-8 {{ $hideTabBar ? 'pb-6' : 'pb-[110px]' }} md:pb-8 min-h-screen md:min-h-[calc(100vh-4rem)]">

        {{-- Flash Messages --}}
        @if(session('status'))
            <div class="mx-5 md:mx-0 mt-3.5 md:mt-0 md:mb-6 flex items-center gap-2.5 p-3.5 rounded-field bg-success-tint border border-success/20">
                <svg class="w-4 h-4 text-success shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <p class="text-[13px] text-success">{{ session('status') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mx-5 md:mx-0 mt-3.5 md:mt-0 md:mb-6 flex items-center gap-2.5 p-3.5 rounded-field bg-danger-tint border border-danger-tint-border">
                <span class="w-2 h-2 rounded-full bg-danger shrink-0"></span>
                <p class="text-[14px] leading-normal text-danger">{{ session('error') }}</p>
            </div>
        @endif

        {{ $slot }}
    </main>

    {{-- Mobile Floating Tab Bar --}}
    @unless($hideTabBar)
    <nav class="md:hidden fixed z-50 flex items-start justify-around left-[14px] right-[14px] bottom-[calc(22px+env(safe-area-inset-bottom))] px-1.5 py-2 bg-white/72 backdrop-blur-xl border border-white/75 rounded-tab-bar shadow-tab-bar">
        @php
            $tabs = [
                ['route' => 'home', 'pattern' => 'home', 'label' => __('Today'), 'path' => 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
                ['route' => 'notifications.index', 'pattern' => 'notifications.*', 'label' => __('Reminders'), 'path' => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0'],
                ['route' => 'events.index', 'pattern' => 'events.*', 'label' => __('History'), 'path' => 'M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                ['route' => 'settings', 'pattern' => ['settings', 'profile.*', 'install'], 'label' => __('More'), 'path' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z', 'path2' => 'M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
            ];
        @endphp

        @foreach($tabs as $tab)
            @php($active = request()->routeIs(...(array) $tab['pattern']))
            <a href="{{ route($tab['route']) }}"
               class="flex flex-col items-center gap-[3px] px-3 py-[5px]">
                <svg class="w-[22px] h-[22px] {{ $active ? 'text-brand' : 'text-text-secondary' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="{{ $tab['path'] }}" />
                    @isset($tab['path2'])
                        <path d="{{ $tab['path2'] }}" />
                    @endisset
                </svg>
                <span class="text-[11px] {{ $active ? 'text-ink font-semibold' : 'text-text-secondary font-medium' }}">{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </nav>
    @endunless

    @livewireScripts
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }

        window.addEventListener('dismiss-push-notification', (e) => {
            if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({
                    type: 'dismiss-notification',
                    eventId: e.detail.eventId,
                });
            }
        });
    </script>
</body>
</html>
