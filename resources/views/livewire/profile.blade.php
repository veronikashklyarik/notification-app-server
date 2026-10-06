<div class="px-5 md:px-0 md:max-w-[640px] md:mx-auto pt-[max(env(safe-area-inset-top),20px)] md:pt-0 pb-8 md:pb-0">
    <a href="{{ route('settings') }}" class="text-[15px] font-semibold text-text-secondary">‹ {{ __('Settings') }}</a>
    <h1 class="mt-3 text-[26px] font-bold tracking-title text-ink">{{ __('Profile') }}</h1>

    @if(session('success'))
        <div class="mt-3.5 p-4 text-sm text-success bg-success-tint rounded-field">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-3.5 space-y-3.5">
        {{-- Avatar card --}}
        <div class="rounded-card bg-white border border-border p-4 flex items-center gap-4" x-data>
            <input type="file" accept="image/jpeg,image/png,image/heic,image/heif,image/webp,.heic,.heif" class="hidden" x-ref="photoInput"
                   x-on:change="$wire.upload('avatar', $event.target.files[0]); $event.target.value = ''">

            <button type="button" @click="$refs.photoInput.click()" wire:loading.attr="disabled" wire:target="updatedAvatar" class="relative shrink-0">
                @if($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-full object-cover">
                @else
                    <div class="w-16 h-16 rounded-full bg-avatar-bg flex items-center justify-center">
                        <span class="text-[24px] font-semibold text-text-secondary">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    </div>
                @endif
                <div class="absolute inset-0 rounded-full bg-ink/40 flex items-center justify-center" wire:loading wire:target="updatedAvatar">
                    <svg class="w-5 h-5 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
            </button>

            <div>
                <button type="button" @click="$refs.photoInput.click()" class="text-[14px] font-semibold text-brand">{{ __('Change photo') }}</button>
                <p class="mt-0.5 text-[12px] text-text-quaternary">{{ __('JPG, PNG or HEIC, up to 2 MB') }}</p>
            </div>
        </div>

        @error('avatar')
            <p class="px-1 text-[13px] text-danger">{{ $message }}</p>
        @enderror

        {{-- Name --}}
        <div x-data="{ saved: $wire.entangle('nameJustSaved') }" x-effect="if (saved) setTimeout(() => saved = false, 2000)">
            <div class="flex items-center justify-between mb-1.5 px-1">
                <label for="profileName" class="text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Name') }}</label>
                <span class="text-[12px] font-semibold text-success" x-show="saved" x-cloak x-transition>{{ __('Saved') }}</span>
            </div>
            <input type="text" id="profileName" wire:model="profileName" wire:change="updateProfile" required
                   class="w-full h-12 px-4 rounded-field border border-border bg-white text-[15px] text-ink">
            @error('profileName')
                <p class="mt-1.5 px-1 text-[13px] text-danger">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label class="block mb-1.5 px-1 text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Email') }}</label>

            @if(!$editingEmail)
                <div class="flex items-center gap-2 h-12 px-4 rounded-field border border-border bg-white">
                    <span class="flex-1 min-w-0 truncate text-[15px] text-ink">{{ $user->email }}</span>
                    <button type="button" wire:click="startEditingEmail" class="shrink-0 text-[13px] font-semibold text-brand">{{ __('Change') }}</button>
                </div>
            @else
                <form wire:submit="changeEmail" class="space-y-2.5">
                    <div>
                        <input type="email" wire:model="newEmail" autofocus placeholder="{{ __('New email address') }}"
                               class="w-full h-12 px-4 rounded-field border bg-white text-[15px] text-ink placeholder:text-text-quaternary {{ $errors->has('newEmail') ? 'border-danger' : 'border-border' }}">
                        @error('newEmail')
                            <p class="mt-1.5 px-1 text-[13px] text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                    @if($user->password)
                        <div>
                            <x-password-input wire:model="emailChangePassword" placeholder="{{ __('Confirm your password') }}" :has-error="$errors->has('emailChangePassword')" />
                            @error('emailChangePassword')
                                <p class="mt-1.5 px-1 text-[13px] text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                    <div class="flex gap-2">
                        <button type="button" wire:click="cancelEditingEmail" class="flex-1 h-11 rounded-control bg-white border border-border text-[14px] font-semibold text-text-secondary">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="changeEmail" class="flex-1 h-11 rounded-control bg-ink text-[14px] font-semibold text-white disabled:opacity-50">
                            <span wire:loading.remove wire:target="changeEmail">{{ __('Save') }}</span>
                            <span wire:loading wire:target="changeEmail">{{ __('Saving...') }}</span>
                        </button>
                    </div>
                </form>
            @endif

            @if(!$user->email_verified_at && !$editingEmail)
                <div class="mt-2 flex items-center gap-2.5 p-3 rounded-control bg-warning-tint border border-warning-tint-border" wire:poll.5s="checkVerificationStatus">
                    <span class="w-2 h-2 rounded-full bg-warning shrink-0"></span>
                    <p class="flex-1 text-[13px] leading-normal text-warning-text">
                        @if($verificationSentAt)
                            {{ __('Not verified yet. Link sent :count min ago — this updates by itself once you tap it.', ['count' => max(0, intdiv(time() - $verificationSentAt, 60))]) }}
                        @else
                            {{ __('Not verified yet.') }}
                        @endif
                    </p>
                    <button type="button" wire:click="sendVerificationEmail" wire:loading.attr="disabled" wire:target="sendVerificationEmail"
                            class="shrink-0 text-[13px] font-semibold text-warning disabled:opacity-50">
                        {{ __('Resend') }}
                    </button>
                </div>
            @endif
        </div>

        {{-- Password --}}
        @if($user->google_id && ! $user->password)
            <div class="rounded-card bg-white border border-border p-4">
                <p class="text-[13px] text-text-tertiary">{{ __('Signed in with Google — no password needed') }}</p>
            </div>
        @else
            <div class="rounded-card bg-white border border-border p-4">
                <form wire:submit="changePassword" class="space-y-3" x-data="{
                    pw: '',
                    confirm: '',
                    get score() {
                        let s = 0;
                        if (this.pw.length >= 8) s++;
                        if (/[a-zA-Z]/.test(this.pw)) s++;
                        if (/[0-9]/.test(this.pw)) s++;
                        return s;
                    },
                    get mismatch() { return this.confirm.length > 0 && this.confirm !== this.pw; }
                }">
                    @if($user->password)
                        <div>
                            <x-password-input wire:model="current_password" placeholder="{{ __('Current password') }}" autocomplete="current-password" />
                            @error('current_password')
                                <p class="mt-1.5 px-1 text-[13px] text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div>
                        <x-password-input wire:model="password" placeholder="{{ __('New password') }}" autocomplete="new-password" x-on:input="pw = $event.target.value" />
                        @error('password')
                            <p class="mt-1.5 px-1 text-[13px] text-danger">{{ $message }}</p>
                        @enderror
                        <x-password-strength />
                    </div>

                    <div>
                        <x-password-input wire:model="password_confirmation" placeholder="{{ __('Confirm new password') }}" autocomplete="new-password" x-on:input="confirm = $event.target.value" />
                        @error('password_confirmation')
                            <p class="mt-1.5 px-1 text-[13px] text-danger">{{ $message }}</p>
                        @enderror
                        <p class="mt-1.5 px-1 text-[13px] text-danger" x-show="mismatch" x-cloak>{{ __('Doesn’t match the new password.') }}</p>
                    </div>

                    <button type="submit" :disabled="score < 3 || mismatch" wire:loading.attr="disabled" wire:target="changePassword"
                            class="w-full h-[50px] rounded-field bg-ink text-[15px] font-semibold text-white disabled:opacity-50">
                        <span wire:loading.remove wire:target="changePassword">{{ __('Update Password') }}</span>
                        <span wire:loading wire:target="changePassword">{{ __('Changing...') }}</span>
                    </button>
                </form>
            </div>
        @endif

        {{-- Danger zone --}}
        <div class="rounded-card bg-white border border-danger-tint-border p-4">
            <p class="text-[11px] font-semibold uppercase tracking-label text-danger">{{ __('Danger Zone') }}</p>
            <p class="mt-2 text-[15px] font-semibold text-ink">{{ __('Delete account') }}</p>
            <p class="mt-1 text-[13px] leading-normal text-text-secondary">{{ __('Reminders stop right away. Sign in within 30 days to restore everything — after that it’s deleted for good.') }}</p>

            <button type="button" wire:click="confirmDeleteAccount"
                    class="mt-3 w-full h-[42px] rounded-control bg-danger-tint text-[15px] font-semibold text-danger">
                {{ __('Delete Account') }}
            </button>
        </div>
    </div>

    @teleport('body')
        <div x-data="{ show: $wire.entangle('confirmingDeleteAccount') }">
            <x-confirm-dialog
                show="show"
                :title="__('Delete your account?')"
                :confirm-label="__('Delete')"
                on-confirm="$wire.deleteAccount()"
                on-cancel="$wire.cancelDeleteAccount()"
            >
                <p>{{ $deleteAccountBody }}</p>

                @if($user->password)
                    <div class="mt-3">
                        <x-password-input wire:model="deletePassword" placeholder="{{ __('Your password') }}" :has-error="$errors->has('deletePassword')" />
                        @error('deletePassword')
                            <p class="mt-1.5 text-[13px] text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </x-confirm-dialog>
        </div>
    @endteleport
</div>
