<x-layouts.legal :title="__('legal.privacy_title')">

    <div class="mt-3 mb-5">
        <h1 class="text-[26px] font-bold tracking-title text-ink">{{ __('legal.privacy_title') }}</h1>
        <p class="mt-1 text-[13px] text-text-tertiary">{{ __('legal.privacy_updated') }}</p>
    </div>

    <div class="rounded-card bg-white border border-border p-5 md:p-6 space-y-5">

        <div>
            <h2 class="text-[15px] font-semibold text-ink mb-1.5">{{ __('legal.privacy_intro_heading') }}</h2>
            <p class="text-[14px] leading-relaxed text-text-secondary">{{ __('legal.privacy_intro') }}</p>
        </div>

        <div>
            <h2 class="text-[15px] font-semibold text-ink mb-1.5">{{ __('legal.privacy_collect_heading') }}</h2>
            <p class="text-[14px] leading-relaxed text-text-secondary mb-2.5">{{ __('legal.privacy_collect') }}</p>
            <ul class="space-y-2">
                @foreach([
                    'legal.privacy_collect_name',
                    'legal.privacy_collect_push',
                    'legal.privacy_collect_timezone',
                    'legal.privacy_collect_google',
                    'legal.privacy_collect_usage',
                ] as $key)
                    <li class="flex items-start gap-2.5 text-[14px] text-text-secondary">
                        <span class="mt-1.5 shrink-0 w-1.5 h-1.5 rounded-full bg-neutral-dot"></span>
                        {{ __($key) }}
                    </li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="text-[15px] font-semibold text-ink mb-1.5">{{ __('legal.privacy_use_heading') }}</h2>
            <p class="text-[14px] leading-relaxed text-text-secondary mb-2.5">{{ __('legal.privacy_use') }}</p>
            <ul class="space-y-2">
                @foreach([
                    'legal.privacy_use_1',
                    'legal.privacy_use_2',
                    'legal.privacy_use_3',
                    'legal.privacy_use_4',
                ] as $key)
                    <li class="flex items-start gap-2.5 text-[14px] text-text-secondary">
                        <svg class="mt-0.5 shrink-0 w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ __($key) }}
                    </li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="text-[15px] font-semibold text-ink mb-1.5">{{ __('legal.privacy_storage_heading') }}</h2>
            <p class="text-[14px] leading-relaxed text-text-secondary">{{ __('legal.privacy_storage') }}</p>
        </div>

        <div>
            <h2 class="text-[15px] font-semibold text-ink mb-1.5">{{ __('legal.privacy_third_party_heading') }}</h2>
            <p class="text-[14px] leading-relaxed text-text-secondary mb-2.5">{{ __('legal.privacy_third_party') }}</p>
            <ul class="space-y-2">
                @foreach([
                    'legal.privacy_third_party_google',
                    'legal.privacy_third_party_push',
                ] as $key)
                    <li class="flex items-start gap-2.5 text-[14px] text-text-secondary">
                        <span class="mt-1.5 shrink-0 w-1.5 h-1.5 rounded-full bg-neutral-dot"></span>
                        {{ __($key) }}
                    </li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="text-[15px] font-semibold text-ink mb-1.5">{{ __('legal.privacy_rights_heading') }}</h2>
            <p class="text-[14px] leading-relaxed text-text-secondary">{{ __('legal.privacy_rights') }}</p>
        </div>

        <div class="pt-4 border-t border-border-light">
            <h2 class="text-[15px] font-semibold text-ink mb-1.5">{{ __('legal.privacy_contact_heading') }}</h2>
            <p class="text-[14px] leading-relaxed text-text-secondary mb-2">{{ __('legal.privacy_contact') }}</p>
            <a href="mailto:{{ config('mail.from.address') }}" class="text-[14px] font-semibold text-brand">
                {{ config('mail.from.address') }}
            </a>
        </div>

    </div>

</x-layouts.legal>
