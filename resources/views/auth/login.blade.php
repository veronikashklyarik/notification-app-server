<x-layouts.guest title="Sign in">

    <h2 class="text-xl font-semibold text-gray-900 mb-6">Sign in to your account</h2>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
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

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
            <x-password-input name="password" autocomplete="current-password" required />
            @error('password')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input type="checkbox" id="remember" name="remember" value="1" class="w-4 h-4 rounded border-gray-300 text-indigo-600 accent-indigo-600">
                <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
            </div>
            <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:text-indigo-700">Forgot password?</a>
        </div>

        <button type="submit"
                class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
            Sign in
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        Don't have an account?
        <a href="{{ route('register') }}" class="text-indigo-600 font-medium hover:text-indigo-700">Create one</a>
    </p>

</x-layouts.guest>
