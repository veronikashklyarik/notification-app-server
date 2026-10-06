<x-layouts.guest title="{{ __('Sign in') }}">

    <div class="mb-5 w-14 h-14 rounded-row bg-brand flex items-center justify-center">
        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
    </div>
    <h1 class="text-[26px] font-bold tracking-title text-ink">{{ __('Welcome back') }}</h1>
    <p class="mt-1.5 text-[15px] text-text-secondary">{{ __('Sign in to your :appName account', ['appName' => config('app.name')]) }}</p>

    @if(session('status'))
        <div class="mt-5 flex items-center gap-2.5 p-3.5 rounded-field bg-success-tint border border-success/20">
            <svg class="w-4 h-4 text-success shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <p class="text-[13px] text-success">{{ session('status') }}</p>
        </div>
    @endif

    <a x-data :href="'{{ route('auth.google') }}?timezone=' + encodeURIComponent(Intl.DateTimeFormat().resolvedOptions().timeZone)"
       class="mt-5 flex items-center justify-center gap-2.5 h-[50px] px-4 rounded-field bg-white border border-border text-[15px] font-semibold text-ink">
        <svg class="w-5 h-5" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        {{ __('Continue with Google') }}
    </a>

    <div class="mt-5 flex items-center gap-3">
        <div class="flex-1 h-px bg-border"></div>
        <div class="text-[11px] font-semibold uppercase tracking-caption text-text-quaternary">{{ __('or') }}</div>
        <div class="flex-1 h-px bg-border"></div>
    </div>

    <form method="POST" action="{{ route('login') }}" class="mt-5 flex flex-col gap-3.5">
        @csrf

        <div>
            <label for="email" class="block mb-1.5 text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Email') }}</label>
            <input type="email"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autofocus
                   autocomplete="email"
                   placeholder="you@example.com"
                   class="w-full h-12 px-4 rounded-field border border-border bg-white text-[15px] text-ink placeholder:text-text-quaternary">
        </div>

        <div>
            <label for="password" class="block mb-1.5 text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Password') }}</label>
            <x-password-input id="password" name="password" autocomplete="current-password" required :hasError="$errors->has('email')" />
            @error('email')
                <p class="mt-1.5 px-1 text-[13px] text-danger">
                    {{ $message }}
                    <a href="{{ route('password.request') }}" class="font-semibold underline">{{ __('Reset password') }}</a>
                </p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" value="1" class="w-5 h-5 rounded-[6px] border-border-input accent-brand">
                <span class="text-[14px] text-text-secondary">{{ __('Keep me signed in') }}</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-[14px] font-semibold text-brand">{{ __('Forgot password?') }}</a>
        </div>

        <button type="submit" class="mt-1.5 h-[50px] rounded-field bg-brand text-[15px] font-semibold text-white">
            {{ __('Sign In') }}
        </button>
    </form>

    <p class="mt-5 text-[14px] text-center text-text-secondary">
        {{ __("Don't have an account?") }}
        <a href="{{ route('register') }}" class="font-semibold text-brand">{{ __('Create one') }}</a>
    </p>

</x-layouts.guest>
