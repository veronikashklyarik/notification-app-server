<x-layouts.guest title="{{ __('Forgot password') }}">

    <a href="{{ route('login') }}" class="text-[15px] font-semibold text-text-secondary">‹ {{ __('Sign in') }}</a>

    @if(session('status'))
        <div x-data="{ seconds: 60 }" x-init="const t = setInterval(() => { if (seconds > 0) seconds--; else clearInterval(t); }, 1000)">
            <div class="mt-5 w-9 h-9 rounded-full bg-success-tint text-success flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
            </div>
            <h1 class="mt-3.5 text-[26px] font-bold tracking-title text-ink">{{ __('Check your inbox') }}</h1>
            <p class="mt-2 text-[14px] leading-normal text-text-secondary">
                {{ __('A reset link is on its way to') }} <strong class="text-ink">{{ old('email') }}</strong>. {{ __('It works once and expires in 60 minutes.') }}
            </p>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <input type="hidden" name="email" value="{{ old('email') }}">
                <button type="submit" :disabled="seconds > 0"
                        class="mt-5 w-full h-12 rounded-field bg-white border border-border text-[15px] font-semibold text-text-secondary disabled:opacity-50">
                    <span x-show="seconds > 0" x-text="@js(__('Resend in')) + ' 0:' + (seconds < 10 ? '0' : '') + seconds"></span>
                    <span x-show="seconds === 0" x-cloak>{{ __('Resend') }}</span>
                </button>
            </form>

            <p class="mt-3.5 text-[14px] text-center text-text-secondary">
                {{ __('Wrong address?') }}
                <a href="{{ route('password.request') }}" class="font-semibold text-brand">{{ __('Use another email') }}</a>
            </p>
        </div>
    @else
        <h1 class="mt-4 text-[26px] font-bold tracking-title text-ink">{{ __('Forgot your password?') }}</h1>
        <p class="mt-2 text-[14px] leading-normal text-text-secondary">{{ __('Enter the email you signed up with and a reset link will arrive within a minute. The link works once and expires in 60 minutes.') }}</p>

        @if($errors->any())
            <div class="mt-5 flex flex-col gap-1.5 p-3.5 rounded-field bg-danger-tint border border-danger-tint-border">
                @foreach($errors->all() as $error)
                    <div class="flex items-start gap-2.5">
                        <span class="mt-[7px] w-2 h-2 rounded-full bg-danger shrink-0"></span>
                        <p class="text-[14px] leading-normal text-danger">{{ $error }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-5 flex flex-col gap-3.5">
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
                       class="w-full h-12 px-4 rounded-field border bg-white text-[15px] text-ink placeholder:text-text-quaternary {{ $errors->has('email') ? 'border-danger' : 'border-border' }}">
            </div>

            <button type="submit" class="mt-1.5 h-[50px] rounded-field bg-brand text-[15px] font-semibold text-white">
                {{ __('Send Reset Link') }}
            </button>
        </form>

        <p class="mt-5 text-[14px] text-center text-text-secondary">
            {{ __('Remember your password?') }}
            <a href="{{ route('login') }}" class="font-semibold text-brand">{{ __('Sign in') }}</a>
        </p>
    @endif

    @unless(session('status'))
        <p class="mt-5 text-[12px] text-center text-text-quaternary">
            <a href="{{ route('legal.privacy') }}">{{ __('Privacy Policy') }}</a>
            <span class="mx-1.5">&middot;</span>
            <a href="{{ route('legal.terms') }}">{{ __('Terms of Service') }}</a>
        </p>
    @endunless

</x-layouts.guest>
