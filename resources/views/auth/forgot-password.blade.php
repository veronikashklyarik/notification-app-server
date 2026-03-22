<x-layouts.guest title="Forgot password">

    <h2 class="text-xl font-semibold text-gray-900 mb-2">Forgot your password?</h2>
    <p class="text-sm text-gray-500 mb-6">Enter your email and we'll send you a reset link.</p>

    @if(session('status'))
        <div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm">
            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
            <input type="email"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autofocus
                   autocomplete="email"
                   placeholder="you@example.com"
                   class="w-full px-3.5 py-2.5 text-sm rounded-lg border transition-colors outline-none
                          {{ $errors->has('email') ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100' : 'border-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100' }}">
            @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
            Send reset link
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        Remember your password?
        <a href="{{ route('login') }}" class="text-indigo-600 font-medium hover:text-indigo-700">Sign in</a>
    </p>

</x-layouts.guest>
