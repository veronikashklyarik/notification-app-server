<x-layouts.guest title="{{ __('Sign in') }}">

    <div class="mb-10 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 mb-5 rounded-3xl gradient-header shadow-lg shadow-indigo-500/25">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </div>
        <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">{{ __('Welcome back') }}</h1>
        <p class="mt-2 text-sm text-gray-400">{{ __('Sign in to your Notifyr account') }}</p>
    </div>

    @if($errors->any())
        <div class="p-4 mb-6 text-sm text-red-600 bg-red-50/80 rounded-2xl border border-red-100">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <a x-data :href="'{{ route('auth.google') }}?timezone=' + encodeURIComponent(Intl.DateTimeFormat().resolvedOptions().timeZone)" class="flex items-center justify-center gap-3 w-full py-3.5 px-4 mb-6 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-2xl hover:bg-gray-50 transition-colors shadow-sm">
        <svg class="w-5 h-5" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        {{ __('Continue with Google') }}
    </a>

    <div class="relative mb-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200"></div>
        </div>
        <div class="relative flex justify-center text-xs uppercase tracking-wider">
            <span class="px-3 bg-white text-gray-400 font-semibold">{{ __('or') }}</span>
        </div>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Email') }}</label>
            <input type="email"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autofocus
                   autocomplete="email"
                   placeholder="you@example.com"
                   class="input-styled w-full {{ $errors->has('email') ? 'border-red-300' : '' }}">
        </div>

        <div>
            <label for="password" class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Password') }}</label>
            <x-password-input name="password" autocomplete="current-password" required :hasError="$errors->has('password')" />
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="remember" name="remember" value="1" class="w-4 h-4 rounded border-gray-300 accent-indigo-600">
                <span class="text-sm text-gray-500">{{ __('Remember me') }}</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">{{ __('Forgot password?') }}</a>
        </div>

        <button type="submit" class="btn-primary w-full py-3.5 text-sm mt-2">
            {{ __('Sign In') }}
        </button>
    </form>

    <p class="mt-8 text-sm text-center text-gray-400">
        {{ __("Don't have an account?") }}
        <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">{{ __('Create one') }}</a>
    </p>

    <p class="mt-6 text-xs text-center text-gray-300">
        <a href="{{ route('legal.privacy') }}" class="hover:text-gray-400 transition-colors">{{ __('Privacy Policy') }}</a>
        <span class="mx-1.5">&middot;</span>
        <a href="{{ route('legal.terms') }}" class="hover:text-gray-400 transition-colors">{{ __('Terms of Service') }}</a>
    </p>

</x-layouts.guest>
