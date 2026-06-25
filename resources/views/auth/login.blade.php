<x-layouts.guest title="Sign in">

    <div class="mb-10 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 mb-5 rounded-3xl gradient-header shadow-lg shadow-indigo-500/25">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </div>
        <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Welcome back</h1>
        <p class="mt-2 text-sm text-gray-400">Sign in to your Notifyr account</p>
    </div>

    @if($errors->any())
        <div class="p-4 mb-6 text-sm text-red-600 bg-red-50/80 rounded-2xl border border-red-100">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</label>
            <input type="email"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autofocus
                   autocomplete="email"
                   placeholder="you@example.com"
                   class="input-styled w-full {{ $errors->has('email') ? 'border-red-300' : '' }}">
            @error('email')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Password</label>
            <x-password-input name="password" autocomplete="current-password" required :hasError="$errors->has('password')" />
            @error('password')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="remember" name="remember" value="1" class="w-4 h-4 rounded border-gray-300 accent-indigo-600">
                <span class="text-sm text-gray-500">Remember me</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Forgot password?</a>
        </div>

        <button type="submit" class="btn-primary w-full py-3.5 text-sm mt-2">
            Sign In
        </button>
    </form>

    <p class="mt-8 text-sm text-center text-gray-400">
        Don't have an account?
        <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">Create one</a>
    </p>

</x-layouts.guest>
