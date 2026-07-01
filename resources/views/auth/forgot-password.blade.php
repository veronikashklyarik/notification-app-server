<x-layouts.guest title="{{ __('Forgot password') }}">

    <div class="mb-10 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 mb-5 rounded-3xl gradient-header shadow-lg shadow-indigo-500/25">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </div>
        <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">{{ __('Reset password') }}</h1>
        <p class="mt-2 text-sm text-gray-400">{{ __('Enter your email to receive a reset link') }}</p>
    </div>

    @if(session('status'))
        <div class="p-4 mb-6 text-sm text-emerald-700 bg-emerald-50/80 rounded-2xl border border-emerald-100 flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 mb-6 text-sm text-red-600 bg-red-50/80 rounded-2xl border border-red-100">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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

        <button type="submit" class="btn-primary w-full py-3.5 text-sm mt-2">
            {{ __('Send Reset Link') }}
        </button>
    </form>

    <p class="mt-8 text-sm text-center text-gray-400">
        {{ __('Remember your password?') }}
        <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">{{ __('Sign in') }}</a>
    </p>

</x-layouts.guest>
