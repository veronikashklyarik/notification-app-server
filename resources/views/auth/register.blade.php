<x-layouts.guest title="Create account">

    <h2 class="text-xl font-semibold text-gray-900 mb-6">Create your account</h2>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full name</label>
            <input type="text"
                   id="name"
                   name="name"
                   value="{{ old('name') }}"
                   required
                   autofocus
                   autocomplete="name"
                   placeholder="Jane Smith"
                   class="w-full px-3.5 py-2.5 text-sm rounded-lg border transition-colors outline-none
                          {{ $errors->has('name') ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100' : 'border-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100' }}">
            @error('name')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
            <input type="email"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
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
            <x-password-input name="password" autocomplete="new-password" placeholder="At least 8 characters" required />
            <p class="mt-1.5 text-xs text-gray-400">Min. 8 characters with at least one letter and one number.</p>
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm password</label>
            <x-password-input name="password_confirmation" id="password_confirmation" autocomplete="new-password" required />
        </div>

        <button type="submit"
                class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
            Create account
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        Already have an account?
        <a href="{{ route('login') }}" class="text-indigo-600 font-medium hover:text-indigo-700">Sign in</a>
    </p>

</x-layouts.guest>
