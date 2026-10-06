<x-layouts.app>
    <div
        x-data="{
            isInstalled: false,
            platform: 'ios',
        }"
        x-init="isInstalled = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                 platform = /Android/.test(navigator.userAgent) ? 'android' : (/iPhone|iPad|iPod/.test(navigator.userAgent) ? 'ios' : 'desktop')"
        class="px-5 md:px-0 md:max-w-[520px] md:mx-auto"
        style="padding-top: max(env(safe-area-inset-top), 20px)"
    >
        <a href="{{ route('settings') }}" class="text-[15px] font-semibold text-text-secondary">‹ {{ __('Settings') }}</a>
        <h1 class="mt-3 text-[26px] font-bold tracking-title text-ink">{{ __('Install :appName', ['appName' => config('app.name')]) }}</h1>

        <div x-show="isInstalled" x-cloak class="mt-3.5 flex items-center gap-2.5 p-3.5 rounded-field bg-success-tint border border-success/20">
            <svg class="w-4 h-4 text-success shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <p class="text-[13px] text-success">{{ __("You're running the app from your Home Screen already.") }}</p>
        </div>

        <div x-show="!isInstalled">
            <p x-show="platform === 'ios'" x-cloak class="mt-1.5 text-[15px] text-text-secondary">{{ __('On iPhone, reminders reach you only from the Home Screen app — in a Safari tab push doesn’t work at all.') }}</p>
            <p x-show="platform === 'android'" x-cloak class="mt-1.5 text-[15px] text-text-secondary">{{ __('On Android, push already works in Chrome — installing just adds a Home Screen icon and its own window.') }}</p>
            <p x-show="platform === 'desktop'" x-cloak class="mt-1.5 text-[15px] text-text-secondary">{{ __('On desktop, push already works in the browser — installing just gives :appName its own window.', ['appName' => config('app.name')]) }}</p>

            {{-- Platform segmented control --}}
            <div class="mt-3.5 flex p-1 rounded-chip bg-fill">
                <button type="button" @click="platform = 'ios'" class="flex-1 h-9 rounded-[10px] text-[13px] font-semibold" :class="platform === 'ios' ? 'bg-white text-ink shadow-xs' : 'text-text-secondary'">
                    {{ __('iPhone') }}
                </button>
                <button type="button" @click="platform = 'android'" class="flex-1 h-9 rounded-[10px] text-[13px] font-semibold" :class="platform === 'android' ? 'bg-white text-ink shadow-xs' : 'text-text-secondary'">
                    {{ __('Android') }}
                </button>
                <button type="button" @click="platform = 'desktop'" class="flex-1 h-9 rounded-[10px] text-[13px] font-semibold" :class="platform === 'desktop' ? 'bg-white text-ink shadow-xs' : 'text-text-secondary'">
                    {{ __('Desktop') }}
                </button>
            </div>

            {{-- iPhone steps --}}
            <div x-show="platform === 'ios'" x-cloak class="mt-3.5 space-y-2">
                <div class="flex items-start gap-3 bg-white border border-border rounded-card p-4">
                    <span class="shrink-0 w-[22px] h-[22px] rounded-full bg-ink text-white text-[12px] font-bold flex items-center justify-center">1</span>
                    <div class="min-w-0">
                        <p class="text-[15px] font-semibold text-ink">{{ __('Open this page in Safari') }}</p>
                        <p class="mt-0.5 text-[13px] text-text-secondary">{{ __("Chrome and Firefox on iPhone can't install apps.") }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-white border border-border rounded-card p-4">
                    <span class="shrink-0 w-[22px] h-[22px] rounded-full bg-ink text-white text-[12px] font-bold flex items-center justify-center">2</span>
                    <div class="min-w-0">
                        <p class="text-[15px] font-semibold text-ink">{{ __('Tap Share') }}</p>
                        <p class="mt-0.5 text-[13px] text-text-secondary">{{ __('The square with an arrow, in the bottom toolbar.') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-white border border-border rounded-card p-4">
                    <span class="shrink-0 w-[22px] h-[22px] rounded-full bg-ink text-white text-[12px] font-bold flex items-center justify-center">3</span>
                    <div class="min-w-0">
                        <p class="text-[15px] font-semibold text-ink">{{ __('Tap "Add to Home Screen", then Add') }}</p>
                        <p class="mt-0.5 text-[13px] text-text-secondary">{{ __('Open :appName from the new icon and turn on notifications in Settings.', ['appName' => config('app.name')]) }}</p>
                    </div>
                </div>
            </div>

            {{-- Android steps --}}
            <div x-show="platform === 'android'" x-cloak class="mt-3.5 space-y-2">
                <div class="flex items-start gap-3 bg-white border border-border rounded-card p-4">
                    <span class="shrink-0 w-[22px] h-[22px] rounded-full bg-ink text-white text-[12px] font-bold flex items-center justify-center">1</span>
                    <div class="min-w-0">
                        <p class="text-[15px] font-semibold text-ink">{{ __('Open this page in Chrome') }}</p>
                        <p class="mt-0.5 text-[13px] text-text-secondary">{{ __('Chrome has the best PWA install support on Android.') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-white border border-border rounded-card p-4">
                    <span class="shrink-0 w-[22px] h-[22px] rounded-full bg-ink text-white text-[12px] font-bold flex items-center justify-center">2</span>
                    <div class="min-w-0">
                        <p class="text-[15px] font-semibold text-ink">{{ __('Tap the three-dot menu') }}</p>
                        <p class="mt-0.5 text-[13px] text-text-secondary">{{ __('Top-right corner of Chrome.') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-white border border-border rounded-card p-4">
                    <span class="shrink-0 w-[22px] h-[22px] rounded-full bg-ink text-white text-[12px] font-bold flex items-center justify-center">3</span>
                    <div class="min-w-0">
                        <p class="text-[15px] font-semibold text-ink">{{ __('Tap "Add to Home screen" or "Install app"') }}</p>
                        <p class="mt-0.5 text-[13px] text-text-secondary">{{ __('Both options work — confirm in the dialog that appears.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Desktop steps --}}
            <div x-show="platform === 'desktop'" x-cloak class="mt-3.5 space-y-2">
                <div class="flex items-start gap-3 bg-white border border-border rounded-card p-4">
                    <span class="shrink-0 w-[22px] h-[22px] rounded-full bg-ink text-white text-[12px] font-bold flex items-center justify-center">1</span>
                    <div class="min-w-0">
                        <p class="text-[15px] font-semibold text-ink">{{ __('Open this page in Chrome or Edge') }}</p>
                        <p class="mt-0.5 text-[13px] text-text-secondary">{{ __('Desktop PWA installs are supported there.') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-white border border-border rounded-card p-4">
                    <span class="shrink-0 w-[22px] h-[22px] rounded-full bg-ink text-white text-[12px] font-bold flex items-center justify-center">2</span>
                    <div class="min-w-0">
                        <p class="text-[15px] font-semibold text-ink">{{ __('Click the install icon in the address bar') }}</p>
                        <p class="mt-0.5 text-[13px] text-text-secondary">{{ __('A small monitor-with-arrow icon, on the right of the address bar.') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-white border border-border rounded-card p-4">
                    <span class="shrink-0 w-[22px] h-[22px] rounded-full bg-ink text-white text-[12px] font-bold flex items-center justify-center">3</span>
                    <div class="min-w-0">
                        <p class="text-[15px] font-semibold text-ink">{{ __('Click Install') }}</p>
                        <p class="mt-0.5 text-[13px] text-text-secondary">{{ __(':appName opens in its own window, with notifications available.', ['appName' => config('app.name')]) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="h-8"></div>
    </div>
</x-layouts.app>
