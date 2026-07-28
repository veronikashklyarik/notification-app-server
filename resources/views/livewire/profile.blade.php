<div class="stagger-children">
    <div class="px-5 pt-6 pb-2">
        <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">{{ __('Settings') }}</h1>
    </div>

    @if(session('success'))
        <div class="px-4 mt-4">
            <div class="p-4 text-sm text-emerald-700 bg-emerald-50 rounded-2xl border border-emerald-100">
                {{ session('success') }}
            </div>
        </div>
    @endif

    {{-- Profile --}}
    <div class="px-4 mt-4">
        <div class="card p-5 space-y-5">
            <div class="flex items-center gap-4" x-data>
                <input type="file" accept="image/jpeg,image/png,image/heic,image/heif,image/webp,.heic,.heif" class="hidden" x-ref="photoInput"
                       x-on:change="$wire.upload('avatar', $event.target.files[0]); $event.target.value = ''">

                <button type="button"
                        @click="$refs.photoInput.click()"
                        wire:loading.attr="disabled"
                        wire:target="updatedAvatar"
                        class="relative shrink-0 touchable">
                    @if($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-2xl object-cover shadow-sm">
                    @else
                        <div class="w-16 h-16 rounded-2xl gradient-header flex items-center justify-center shadow-lg shadow-indigo-500/20">
                            <span class="text-2xl font-bold text-white">{{ substr($user->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <div class="absolute inset-0 rounded-2xl bg-black/25 flex items-center justify-center" wire:loading wire:target="updatedAvatar">
                        <svg class="w-5 h-5 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                    <div class="absolute bottom-0 right-0 w-5 h-5 rounded-full bg-indigo-600 border-2 border-white flex items-center justify-center" wire:loading.remove wire:target="updatedAvatar">
                        <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </button>

                <div class="flex-1 min-w-0">
                    <p class="text-lg font-bold text-gray-900">{{ $user->name }}</p>
                    <p class="text-sm text-gray-400 break-all">{{ $user->email }}</p>
                    @if($user->email_verified_at)
                        <span class="inline-flex items-center gap-1 mt-1 text-xs font-semibold text-emerald-600">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Verified') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 mt-1 text-xs font-semibold text-amber-500">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Not verified') }}
                        </span>
                    @endif
                </div>
            </div>

            @error('profileName')
                <div class="p-3 text-sm text-red-600 bg-red-50/80 rounded-xl border border-red-100">{{ $message }}</div>
            @enderror
            @error('avatar')
                <div class="p-3 text-sm text-red-600 bg-red-50/80 rounded-xl border border-red-100">{{ $message }}</div>
            @enderror

            <form wire:submit="updateProfile" class="space-y-4">
                <div>
                    <label for="profileName" class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Name') }}</label>
                    <input type="text" id="profileName" wire:model="profileName" required class="input-styled w-full">
                </div>

                <div>
                    <label for="timezone" class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Timezone') }}</label>
                    <select id="timezone" wire:model="timezone" class="input-styled w-full">
                        @foreach($timezones as $tz)
                            <option value="{{ $tz }}">{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="locale" class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Language') }}</label>
                    <select id="locale" wire:model="locale" wire:change="updateLang" class="input-styled w-full">
                        @foreach(\App\Livewire\Profile::SUPPORTED_LOCALES as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-primary w-full py-3 text-sm" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="updateProfile">{{ __('Save Profile') }}</span>
                    <span wire:loading wire:target="updateProfile">{{ __('Saving...') }}</span>
                </button>
            </form>

            @if(!$user->email_verified_at)
                <div class="pt-3 border-t border-amber-100">
                    @if($verificationEmailSent)
                        <div class="flex items-start gap-3 p-3 bg-emerald-50 rounded-xl">
                            <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-emerald-700">{{ __('Verification email sent!') }}</p>
                                <p class="text-xs text-emerald-600 mt-0.5">{{ __('Check your inbox and click the link, then tap below.') }}</p>
                            </div>
                        </div>
                        @error('verification')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <button wire:click="checkVerificationStatus"
                                wire:loading.attr="disabled"
                                wire:target="checkVerificationStatus"
                                class="mt-3 w-full py-2.5 text-sm font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 active:scale-[0.98] transition-all disabled:opacity-50">
                            <span wire:loading.remove wire:target="checkVerificationStatus">{{ __("I've verified my email") }}</span>
                            <span wire:loading wire:target="checkVerificationStatus">{{ __('Checking...') }}</span>
                        </button>
                    @else
                        <div class="flex items-start gap-3 p-3 bg-amber-50 rounded-xl">
                            <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-amber-700">{{ __('Email not verified') }}</p>
                                <p class="text-xs text-amber-600 mt-0.5">{{ __('Verify your email to unlock all features.') }}</p>
                            </div>
                        </div>
                        <button wire:click="sendVerificationEmail"
                                wire:loading.attr="disabled"
                                wire:target="sendVerificationEmail"
                                class="mt-3 w-full py-2.5 text-sm font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-xl hover:bg-amber-100 active:scale-[0.98] transition-all disabled:opacity-50">
                            <span wire:loading.remove wire:target="sendVerificationEmail">{{ __('Send Verification Email') }}</span>
                            <span wire:loading wire:target="sendVerificationEmail">{{ __('Sending...') }}</span>
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Change / Set Password --}}
    <div class="px-4 mt-4" x-data="{ open: false }">
        <div class="card p-5">
            <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ $user->password ? __('Change Password') : __('Set Password') }}</h2>
                <svg class="w-5 h-5 text-gray-300 transition-transform duration-200" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>

            <div x-show="open" x-cloak x-transition class="mt-4">
                <form wire:submit="changePassword" class="space-y-4">
                    @if($user->password)
                        <div>
                            <x-password-input wire:model="current_password" required placeholder="{{ __('Current password') }}" />
                            @error('current_password')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                    <div>
                        <x-password-input wire:model="password" required placeholder="{{ __('New password') }}" />
                        @error('password')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-password-input wire:model="password_confirmation" required placeholder="{{ __('Confirm new password') }}" />
                        @error('password_confirmation')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn-primary w-full py-3 text-sm" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="changePassword">{{ $user->password ? __('Change Password') : __('Set Password') }}</span>
                        <span wire:loading wire:target="changePassword">{{ __('Changing...') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Push Notifications --}}
    <div class="px-4 mt-4">
        <div class="card p-4">
            <x-push-notification-settings />
        </div>
    </div>

    {{-- Add to Home Screen --}}
    <div x-data class="px-4 mt-4">
        <template x-if="!window.matchMedia('(display-mode: standalone)').matches && window.navigator.standalone !== true">
            <a href="{{ route('install') }}" class="card flex items-center justify-between p-4 w-full">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ __('Add to Home Screen') }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ __('Install as an app for the best experience') }}</p>
                    </div>
                </div>
                <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </template>
    </div>

    {{-- Log Out --}}
    <div class="px-4 mt-6">
        <button wire:click="logout" wire:loading.attr="disabled" wire:target="logout" class="w-full py-3.5 text-sm font-bold text-red-500 bg-white rounded-xl border border-gray-200 hover:bg-red-50 hover:border-red-200 active:scale-[0.98] transition-all shadow-sm disabled:opacity-50">
            <span wire:loading.remove wire:target="logout">{{ __('Log Out') }}</span>
            <span wire:loading wire:target="logout">{{ __('Logging out...') }}</span>
        </button>
    </div>

    {{-- Danger Zone --}}
    <div class="px-4 mt-4 mb-6" x-data="{ open: false }">
        <div class="card p-5 border-red-100">
            <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
                <h2 class="text-xs font-bold text-red-400 uppercase tracking-widest">{{ __('Danger Zone') }}</h2>
                <svg class="w-5 h-5 text-gray-300 transition-transform duration-200" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>

            <div x-show="open" x-cloak x-transition class="mt-4">
                @error('deletePassword')
                    <div class="p-3 mb-4 text-sm text-red-600 bg-red-50/80 rounded-xl border border-red-100">{{ $message }}</div>
                @enderror

                <p class="text-sm text-gray-400 mb-4">{{ __('Permanently delete your account and all data. This cannot be undone.') }}</p>

                <form wire:submit="confirmDeleteAccount" class="space-y-3">
                    @if($user->password)
                        <x-password-input wire:model="deletePassword" required placeholder="{{ __('Confirm your password') }}" />
                    @endif
                    <button type="submit" wire:loading.attr="disabled" wire:target="confirmDeleteAccount,deleteAccount" class="w-full py-3 text-sm font-bold text-white bg-red-500 rounded-xl shadow-md shadow-red-500/20 hover:bg-red-600 active:scale-[0.98] transition-all disabled:opacity-50">
                        <span wire:loading.remove wire:target="confirmDeleteAccount,deleteAccount">{{ __('Delete Account') }}</span>
                        <span wire:loading wire:target="confirmDeleteAccount,deleteAccount">{{ __('Deleting...') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    @teleport('body')
    <div x-data="{ confirmingDelete: false }"
         x-on:show-delete-account-confirmation.window="confirmingDelete = true"
         x-show="confirmingDelete" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="confirmingDelete = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-6"
         @click.self="confirmingDelete = false">
        <div x-show="confirmingDelete"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-sm bg-white rounded-3xl p-6 shadow-2xl">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900">{{ __('Delete Account') }}</h3>
            </div>
            <p class="text-sm text-gray-500 mb-5">{{ __('Are you sure? This will permanently delete your account and all data. This action cannot be undone.') }}</p>
            <div class="flex gap-3">
                <button type="button" @click="confirmingDelete = false"
                        class="flex-1 py-3 text-sm font-bold text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 active:scale-[0.98] transition-all">
                    {{ __('Cancel') }}
                </button>
                <button type="button"
                        @click="confirmingDelete = false; $wire.deleteAccount()"
                        class="flex-1 py-3 text-sm font-bold text-red-500 bg-gray-100 rounded-xl hover:bg-red-50 active:scale-[0.98] transition-all">
                    {{ __('Delete') }}
                </button>
            </div>
        </div>
    </div>
    @endteleport
</div>
