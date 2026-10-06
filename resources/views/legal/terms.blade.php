<x-layouts.legal :title="__('legal.terms_title')">

    <div class="mt-3 mb-5">
        <h1 class="text-[26px] font-bold tracking-title text-ink">{{ __('legal.terms_title') }}</h1>
        <p class="mt-1 text-[13px] text-text-tertiary">{{ __('legal.terms_updated') }}</p>
    </div>

    <div class="rounded-card bg-white border border-border p-5 md:p-6 space-y-5">

        <div>
            <h2 class="text-[15px] font-semibold text-ink mb-1.5">{{ __('legal.terms_acceptance_heading') }}</h2>
            <p class="text-[14px] leading-relaxed text-text-secondary">{{ __('legal.terms_acceptance') }}</p>
        </div>

        <div>
            <h2 class="text-[15px] font-semibold text-ink mb-1.5">{{ __('legal.terms_service_heading') }}</h2>
            <p class="text-[14px] leading-relaxed text-text-secondary">{{ __('legal.terms_service') }}</p>
        </div>

        <div>
            <h2 class="text-[15px] font-semibold text-ink mb-1.5">{{ __('legal.terms_accounts_heading') }}</h2>
            <p class="text-[14px] leading-relaxed text-text-secondary">{{ __('legal.terms_accounts') }}</p>
        </div>

        <div>
            <h2 class="text-[15px] font-semibold text-ink mb-1.5">{{ __('legal.terms_prohibited_heading') }}</h2>
            <p class="text-[14px] leading-relaxed text-text-secondary mb-2.5">{{ __('legal.terms_prohibited') }}</p>
            <ul class="space-y-2">
                @foreach([
                    'legal.terms_prohibited_1',
                    'legal.terms_prohibited_2',
                    'legal.terms_prohibited_3',
                    'legal.terms_prohibited_4',
                ] as $key)
                    <li class="flex items-start gap-2.5 text-[14px] text-text-secondary">
                        <svg class="mt-0.5 shrink-0 w-4 h-4 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        {{ __($key) }}
                    </li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="text-[15px] font-semibold text-ink mb-1.5">{{ __('legal.terms_ip_heading') }}</h2>
            <p class="text-[14px] leading-relaxed text-text-secondary">{{ __('legal.terms_ip') }}</p>
        </div>

        <div class="p-4 rounded-field bg-warning-tint border border-warning-tint-border">
            <h2 class="text-[15px] font-semibold text-ink mb-1.5">{{ __('legal.terms_liability_heading') }}</h2>
            <p class="text-[14px] leading-relaxed text-warning-text">{{ __('legal.terms_liability') }}</p>
        </div>

        <div>
            <h2 class="text-[15px] font-semibold text-ink mb-1.5">{{ __('legal.terms_changes_heading') }}</h2>
            <p class="text-[14px] leading-relaxed text-text-secondary">{{ __('legal.terms_changes') }}</p>
        </div>

        <div class="pt-4 border-t border-border-light">
            <h2 class="text-[15px] font-semibold text-ink mb-1.5">{{ __('legal.terms_contact_heading') }}</h2>
            <p class="text-[14px] leading-relaxed text-text-secondary mb-2">{{ __('legal.terms_contact') }}</p>
            <a href="mailto:{{ config('mail.from.address') }}" class="text-[14px] font-semibold text-brand">
                {{ config('mail.from.address') }}
            </a>
        </div>

    </div>

</x-layouts.legal>
