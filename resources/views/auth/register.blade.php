<x-layouts.guest title="{{ __('Create account') }}">

    <h1 class="text-[26px] font-bold tracking-title text-ink">{{ __('Get started') }}</h1>
    <p class="mt-1.5 text-[15px] text-text-secondary">{{ __('Create your :appName account', ['appName' => config('app.name')]) }}</p>

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

    <form method="POST" action="{{ route('register') }}" class="mt-5 flex flex-col gap-3"
          x-data="{
              pw: '',
              get score() {
                  let s = 0;
                  if (this.pw.length >= 8) s++;
                  if (/[a-zA-Z]/.test(this.pw)) s++;
                  if (/[0-9]/.test(this.pw)) s++;
                  if (/[^a-zA-Z0-9]/.test(this.pw)) s++;
                  return s;
              },
              get valid() { return this.score >= 3; }
          }"
          x-init="$el.querySelector('[name=timezone]').value = Intl.DateTimeFormat().resolvedOptions().timeZone">
        @csrf
        <input type="hidden" name="timezone" value="UTC">

        <div>
            <label for="name" class="block mb-1.5 text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Name') }}</label>
            <input type="text"
                   id="name"
                   name="name"
                   value="{{ old('name') }}"
                   required
                   autofocus
                   autocomplete="name"
                   placeholder="{{ __('Your name') }}"
                   class="w-full h-12 px-4 rounded-field border bg-white text-[15px] text-ink placeholder:text-text-quaternary {{ $errors->has('name') ? 'border-danger' : 'border-border' }}">
            @error('name')
                <p class="mt-1.5 px-1 text-[13px] text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block mb-1.5 text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Email') }}</label>
            <input type="email"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autocomplete="email"
                   placeholder="you@example.com"
                   class="w-full h-12 px-4 rounded-field border bg-white text-[15px] text-ink placeholder:text-text-quaternary {{ $errors->has('email') ? 'border-danger' : 'border-border' }}">
            @error('email')
                <p class="mt-1.5 px-1 text-[13px] text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block mb-1.5 text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Password') }}</label>
            <x-password-input id="password" name="password" autocomplete="new-password" placeholder="{{ __('Min. 8 characters') }}" required :hasError="$errors->has('password')" x-on:input="pw = $event.target.value" />
            @error('password')
                <p class="mt-1.5 px-1 text-[13px] text-danger">{{ $message }}</p>
            @enderror
            <x-password-strength />
        </div>

        <button type="submit" :disabled="!valid" class="mt-1.5 h-[50px] rounded-field bg-brand text-[15px] font-semibold text-white disabled:opacity-50">
            {{ __('Create Account') }}
        </button>
    </form>

    <p class="mt-5 text-[14px] text-center text-text-secondary">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" class="font-semibold text-brand">{{ __('Sign in') }}</a>
    </p>

    <p class="mt-5 text-[12px] text-center text-text-quaternary">
        <a href="{{ route('legal.privacy') }}">{{ __('Privacy Policy') }}</a>
        <span class="mx-1.5">&middot;</span>
        <a href="{{ route('legal.terms') }}">{{ __('Terms of Service') }}</a>
    </p>

</x-layouts.guest>
