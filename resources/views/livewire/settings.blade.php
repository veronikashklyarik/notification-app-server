<div class="px-5 md:px-0 md:max-w-[640px] md:mx-auto pt-[max(env(safe-area-inset-top),20px)] md:pt-0 pb-8 md:pb-0">
    <h1 class="text-[26px] font-bold tracking-title text-ink">{{ __('Settings') }}</h1>

    <div class="mt-3.5 space-y-3.5">
        {{-- Account row --}}
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-card bg-white border border-border px-4 py-[15px]">
            @if($user->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="" class="w-11 h-11 rounded-full object-cover shrink-0">
            @else
                <div class="w-11 h-11 rounded-full bg-avatar-bg flex items-center justify-center shrink-0">
                    <span class="text-[16px] font-semibold text-text-secondary">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                </div>
            @endif
            <div class="min-w-0 flex-1">
                <p class="text-[16px] font-semibold text-ink truncate">{{ $user->name }}</p>
                <p class="text-[13px] text-text-tertiary truncate">{{ $user->email }}</p>
            </div>
            <svg class="w-4 h-4 text-neutral-dot shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 5l7 7-7 7" />
            </svg>
        </a>

        {{-- Notifications group --}}
        <div>
            <p class="mb-2 px-1 text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('Notifications') }}</p>
            <x-push-notification-settings />
        </div>

        {{-- App group --}}
        <div>
            <p class="mb-2 px-1 text-[12px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('App') }}</p>
            <div class="rounded-card bg-white border border-border divide-y divide-border-light overflow-hidden">
                <label class="flex items-center justify-between px-4 py-3.5">
                    <span class="text-[15px] text-ink">{{ __('Language') }}</span>
                    <span class="relative flex items-center gap-1">
                        <select wire:model.live="locale" wire:change="updateLang"
                                class="appearance-none bg-transparent border-none text-[15px] font-semibold text-brand text-right pr-4 focus:ring-0 focus:outline-none">
                            @foreach(\App\Enums\Locale::cases() as $localeCase)
                                <option value="{{ $localeCase->value }}">{{ $localeCase->label() }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-0 text-[18px] text-neutral-dot">›</span>
                    </span>
                </label>

                <label class="flex items-center justify-between px-4 py-3.5">
                    <span class="text-[15px] text-ink shrink-0">{{ __('Time zone') }}</span>
                    <span class="relative flex items-center gap-1 min-w-0">
                        <select wire:model.live="timezone" wire:change="updateTimezone"
                                class="appearance-none bg-transparent border-none text-[15px] font-semibold text-brand text-right pr-4 focus:ring-0 focus:outline-none max-w-[180px]">
                            @foreach($timezoneGroups as $region => $zones)
                                <optgroup label="{{ __($region) }}">
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone['value'] }}">{{ $zone['label'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-0 text-[18px] text-neutral-dot">›</span>
                    </span>
                </label>

                <a href="{{ route('legal.privacy') }}" class="flex items-center justify-between px-4 py-3.5">
                <span class="text-[15px] text-ink">{{ __('Privacy Policy') }}</span>
                <svg class="w-4 h-4 text-neutral-dot shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 5l7 7-7 7" />
                </svg>
            </a>

            <a href="{{ route('legal.terms') }}" class="flex items-center justify-between px-4 py-3.5">
                <span class="text-[15px] text-ink">{{ __('Terms of Service') }}</span>
                <svg class="w-4 h-4 text-neutral-dot shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 5l7 7-7 7" />
                </svg>
            </a>
            </div>
        </div>

        {{-- Log Out --}}
        <button type="button" wire:click="logout" wire:loading.attr="disabled" wire:target="logout"
                class="w-full h-[50px] rounded-field bg-white border border-border text-[15px] font-semibold text-danger disabled:opacity-50">
            <span wire:loading.remove wire:target="logout">{{ __('Log Out') }}</span>
            <span wire:loading wire:target="logout">{{ __('Logging out...') }}</span>
        </button>

        <p class="text-center text-[12px] text-text-quaternary">
            {{ __(':appName :version', ['appName' => config('app.name'), 'version' => $appVersion]) }}
            <span class="mx-1">·</span>
            <a href="mailto:{{ config('mail.from.address') }}" class="font-semibold">{{ __('Contact support') }}</a>
        </p>
    </div>
</div>
