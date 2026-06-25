<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }} - Notifyr</title>

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Notifyr">
    <meta name="theme-color" content="#6366f1">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="apple-touch-startup-image" href="/splash.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 md:bg-gray-50 mobile-gradient-bg antialiased">

    {{-- Navigation --}}
    <nav class="hidden md:block bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-16">

                {{-- Logo --}}
                <div class="flex items-center gap-6 md:gap-8">
                    <a href="{{ route('notifications.index') }}" class="flex items-center gap-2.5 md:gap-2">
                        <div class="w-10 h-10 md:w-8 md:h-8 bg-indigo-600 rounded-2xl md:rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 md:w-5 md:h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                        </div>
                        <span class="font-bold text-gray-900 text-xl md:text-lg">Notifyr</span>
                    </a>

                    {{-- Desktop Navigation --}}
                    <div class="hidden md:flex items-center gap-1">
                        <a href="{{ route('home') }}"
                           class="px-3 py-2 rounded-md text-sm font-medium transition-colors
                                  {{ request()->routeIs('home') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                            Home
                        </a>
                        <a href="{{ route('notifications.index') }}"
                           class="px-3 py-2 rounded-md text-sm font-medium transition-colors
                                  {{ request()->routeIs('notifications.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                            Notifications
                        </a>
                        <a href="{{ route('events.index') }}"
                           class="px-3 py-2 rounded-md text-sm font-medium transition-colors
                                  {{ request()->routeIs('events.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                            Events
                        </a>
                        <a href="{{ route('history.index') }}"
                           class="px-3 py-2 rounded-md text-sm font-medium transition-colors
                                  {{ request()->routeIs('history.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                            History
                        </a>
                    </div>
                </div>

                {{-- Desktop User Menu --}}
                <div class="hidden md:block relative" x-data="{ open: false }">
                    <button @click="open = !open" @click.outside="open = false"
                            class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-100 transition-all duration-200">
                        <div class="w-8 h-8 rounded-full overflow-hidden bg-indigo-100 flex items-center justify-center flex-shrink-0">
                            @if(auth()->user()->avatar_url)
                                <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="w-full h-full object-cover">
                            @else
                                <span class="text-indigo-700 font-semibold text-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            @endif
                        </div>
                        <span class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-1 w-48 bg-white rounded-xl shadow-lg border border-gray-200 py-1 z-50">
                        <div class="px-3 py-2 border-b border-gray-100">
                            <p class="text-xs text-gray-500">Signed in as</p>
                            <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            Profile
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                </svg>
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Mobile User Avatar (No Dropdown) --}}
                <div class="md:hidden">
                    <a href="{{ route('profile.edit') }}" class="block">
                        <div class="w-10 h-10 rounded-full overflow-hidden bg-indigo-100 flex items-center justify-center ring-2 ring-indigo-100">
                            @if(auth()->user()->avatar_url)
                                <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="w-full h-full object-cover">
                            @else
                                <span class="text-indigo-700 font-bold text-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            @endif
                        </div>
                    </a>
                </div>

            </div>
        </div>
    </nav>

    {{-- Page Content --}}
    <main class="max-w-6xl mx-auto px-0 md:px-6 py-0 md:py-8 pb-24 md:pb-8 min-h-screen md:min-h-[calc(100vh-4rem)]">

        {{-- Flash Messages --}}
        @if(session('status'))
            <div class="mb-5 md:mb-6 flex items-center gap-3 bg-gradient-to-br from-green-50 to-emerald-50 md:bg-green-50 border-2 md:border border-green-200 text-green-800 px-4 md:px-4 py-3.5 md:py-3 rounded-2xl md:rounded-xl text-sm font-medium md:font-normal shadow-lg shadow-green-500/10 md:shadow-none slide-up">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('status') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-5 md:mb-6 flex items-center gap-3 bg-gradient-to-br from-red-50 to-rose-50 md:bg-red-50 border-2 md:border border-red-200 text-red-800 px-4 md:px-4 py-3.5 md:py-3 rounded-2xl md:rounded-xl text-sm font-medium md:font-normal shadow-lg shadow-red-500/10 md:shadow-none slide-up">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    {{-- Mobile Bottom Navigation --}}
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50 safe-area-bottom">
        <div class="flex items-center justify-around px-2 py-1 max-w-lg mx-auto">
            {{-- Home Tab --}}
            <a href="{{ route('home') }}"
               class="flex flex-col items-center justify-center flex-1 py-1.5 px-2 transition-colors duration-200">
                <div class="flex items-center justify-center w-8 h-8 mb-0.5 rounded-lg {{ request()->routeIs('home') ? 'ring-1.5 ring-indigo-500' : '' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('home') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                </div>
                <span class="text-[10px] font-semibold {{ request()->routeIs('home') ? 'text-indigo-600' : 'text-gray-400' }}">Home</span>
            </a>

            {{-- Notifications Tab --}}
            <a href="{{ route('notifications.index') }}"
               class="flex flex-col items-center justify-center flex-1 py-1.5 px-2 transition-colors duration-200">
                <div class="flex items-center justify-center w-8 h-8 mb-0.5 rounded-lg {{ request()->routeIs('notifications.*') ? 'ring-1.5 ring-indigo-500' : '' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('notifications.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                </div>
                <span class="text-[10px] font-semibold {{ request()->routeIs('notifications.*') ? 'text-indigo-600' : 'text-gray-400' }}">Reminders</span>
            </a>

            {{-- Events Tab --}}
            <a href="{{ route('events.index') }}"
               class="flex flex-col items-center justify-center flex-1 py-1.5 px-2 transition-colors duration-200">
                <div class="flex items-center justify-center w-8 h-8 mb-0.5 rounded-lg {{ request()->routeIs('events.*') ? 'ring-1.5 ring-indigo-500' : '' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('events.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="text-[10px] font-semibold {{ request()->routeIs('events.*') ? 'text-indigo-600' : 'text-gray-400' }}">Events</span>
            </a>

            {{-- Settings Tab --}}
            <a href="{{ route('profile.edit') }}"
               class="flex flex-col items-center justify-center flex-1 py-1.5 px-2 transition-colors duration-200">
                <div class="flex items-center justify-center w-8 h-8 mb-0.5 rounded-lg {{ request()->routeIs('profile.*') ? 'ring-1.5 ring-indigo-500' : '' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('profile.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <span class="text-[10px] font-semibold {{ request()->routeIs('profile.*') ? 'text-indigo-600' : 'text-gray-400' }}">Settings</span>
            </a>
        </div>
    </nav>

    @livewireScripts
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }
    </script>
</body>
</html>
