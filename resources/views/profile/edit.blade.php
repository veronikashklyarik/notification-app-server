<x-layouts.app title="Profile">

    {{-- Page Header --}}
    <div class="mb-6 md:mb-8">
        <h1 class="text-2xl font-bold md:font-semibold text-gray-900">Profile</h1>
        <p class="text-gray-500 text-sm mt-1.5 md:mt-1">Manage your account information and password.</p>
    </div>

    <div class="grid gap-6 max-w-2xl">

        {{-- Profile Info --}}
        <div class="bg-white rounded-3xl md:rounded-2xl border md:border border-gray-100 md:border-gray-200 p-6 shadow-sm md:shadow-none">
            <h2 class="text-base font-bold md:font-semibold text-gray-900 mb-5">Account information</h2>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PUT')

                {{-- Avatar --}}
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 rounded-full overflow-hidden bg-indigo-100 flex items-center justify-center flex-shrink-0">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="Avatar" class="w-full h-full object-cover" id="avatar-preview">
                        @else
                            <span class="text-indigo-600 font-semibold text-xl" id="avatar-initial">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            <img src="" alt="" class="w-full h-full object-cover hidden" id="avatar-preview">
                        @endif
                    </div>
                    <div>
                        <label for="avatar" class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                            Upload photo
                        </label>
                        <input type="file" id="avatar" name="avatar" accept="image/*" class="hidden"
                               onchange="previewAvatar(this)">
                        <p class="text-xs text-gray-400 mt-1.5">JPG, PNG or WebP · Max 2 MB</p>
                        @error('avatar')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full name</label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $user->name) }}"
                           required
                           class="w-full px-3.5 py-2.5 text-sm rounded-lg border transition-colors outline-none
                                  {{ $errors->has('name') ? 'border-red-300 bg-red-50' : 'border-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100' }}">
                    @error('name')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email', $user->email) }}"
                           required
                           class="w-full px-3.5 py-2.5 text-sm rounded-lg border transition-colors outline-none
                                  {{ $errors->has('email') ? 'border-red-300 bg-red-50' : 'border-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100' }}">
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1.5">Timezone</label>
                    @php
                        $timezones = collect(\DateTimeZone::listIdentifiers())
                            ->groupBy(fn ($tz) => str_contains($tz, '/') ? explode('/', $tz)[0] : 'Other')
                            ->sortKeys();
                    @endphp
                    <select id="timezone"
                            name="timezone"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border transition-colors outline-none bg-white
                                   {{ $errors->has('timezone') ? 'border-red-300 bg-red-50' : 'border-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100' }}">
                        @foreach($timezones as $region => $zones)
                            <optgroup label="{{ $region }}">
                                @foreach($zones as $tz)
                                    <option value="{{ $tz }}" {{ old('timezone', $user->timezone ?? 'UTC') === $tz ? 'selected' : '' }}>
                                        {{ str_replace(['_', '/'], [' ', ' / '], $tz) }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('timezone')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full md:w-auto h-12 md:h-auto px-4 md:px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-98 md:active:scale-100 text-white text-sm font-bold md:font-medium rounded-2xl md:rounded-lg transition-all shadow-lg shadow-indigo-500/30 md:shadow-none">
                    Save changes
                </button>
            </form>
        </div>

        {{-- Change Password --}}
        <div class="bg-white rounded-3xl md:rounded-2xl border md:border border-gray-100 md:border-gray-200 p-6 shadow-sm md:shadow-none">
            <h2 class="text-base font-bold md:font-semibold text-gray-900 mb-1">Change password</h2>
            <p class="text-sm text-gray-500 mb-5">Leave blank to keep your current password.</p>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('PUT')
                <input type="hidden" name="name" value="{{ $user->name }}">
                <input type="hidden" name="email" value="{{ $user->email }}">
                <input type="hidden" name="timezone" value="{{ $user->timezone ?? 'UTC' }}">

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1.5">Current password</label>
                    <x-password-input name="current_password" autocomplete="current-password" :hasError="$errors->has('current_password')" />
                    @error('current_password')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">New password</label>
                    <x-password-input name="password" id="password" autocomplete="new-password" placeholder="At least 8 characters" :hasError="$errors->has('password')" />
                    <p class="mt-1.5 text-xs text-gray-400">Min. 8 characters with at least one letter and one number.</p>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm new password</label>
                    <x-password-input name="password_confirmation" id="password_confirmation" autocomplete="new-password" />
                </div>

                <button type="submit"
                        class="w-full md:w-auto h-12 md:h-auto px-4 md:px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-98 md:active:scale-100 text-white text-sm font-bold md:font-medium rounded-2xl md:rounded-lg transition-all shadow-lg shadow-indigo-500/30 md:shadow-none">
                    Update password
                </button>
            </form>
        </div>

        {{-- Notifications --}}
        <div class="bg-white rounded-3xl md:rounded-2xl border md:border border-gray-100 md:border-gray-200 p-6 shadow-sm md:shadow-none">
            <h2 class="text-base font-bold md:font-semibold text-gray-900 mb-5">Notifications</h2>
            <x-push-notification-settings />
        </div>

    </div>

    {{-- Mobile Sign Out Button --}}
    <div class="md:hidden mt-8 max-w-2xl">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center justify-center gap-2.5 h-12 bg-white border-2 border-red-200 hover:bg-red-50 active:bg-red-100 text-red-600 font-bold rounded-2xl transition-all active:scale-98 shadow-sm">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                </svg>
                Sign out
            </button>
        </form>
    </div>

    <script>
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('avatar-preview');
                    const initial = document.getElementById('avatar-initial');
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    if (initial) initial.classList.add('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

</x-layouts.app>
