<x-layouts.guest title="Reset password">

    <h2 class="text-xl font-semibold text-gray-900 mb-6">Set a new password</h2>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
            <input type="email"
                   id="email"
                   name="email"
                   value="{{ old('email', $email) }}"
                   required
                   autocomplete="email"
                   class="w-full px-3.5 py-2.5 text-sm rounded-lg border transition-colors outline-none
                          {{ $errors->has('email') ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100' : 'border-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100' }}">
            @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">New password</label>
            <x-password-input name="password" autocomplete="new-password" placeholder="At least 8 characters" required :hasError="$errors->has('password')" />
            <p class="mt-1.5 text-xs text-gray-400">Min. 8 characters with at least one letter and one number.</p>
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm new password</label>
            <x-password-input name="password_confirmation" id="password_confirmation" autocomplete="new-password" required />
        </div>

        <button type="submit"
                class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
            Reset password
        </button>
    </form>

</x-layouts.guest>
