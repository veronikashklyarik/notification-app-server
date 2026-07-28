<x-layouts.legal :title="__('legal.terms_title')">

    {{-- Hero --}}
    <div class="mb-10">
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">{{ __('legal.terms_title') }}</h1>
        <p class="mt-2 text-sm text-gray-400">{{ __('legal.terms_updated') }}</p>
    </div>

    {{-- Sections --}}
    <div class="space-y-4">

        @php $n = 1; @endphp

        {{-- Acceptance --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center mt-0.5">{{ $n++ }}</span>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">{{ __('legal.terms_acceptance_heading') }}</h2>
                    <p class="text-sm text-gray-500 leading-relaxed">{{ __('legal.terms_acceptance') }}</p>
                </div>
            </div>
        </div>

        {{-- Service description --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center mt-0.5">{{ $n++ }}</span>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">{{ __('legal.terms_service_heading') }}</h2>
                    <p class="text-sm text-gray-500 leading-relaxed">{{ __('legal.terms_service') }}</p>
                </div>
            </div>
        </div>

        {{-- Accounts --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center mt-0.5">{{ $n++ }}</span>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">{{ __('legal.terms_accounts_heading') }}</h2>
                    <p class="text-sm text-gray-500 leading-relaxed">{{ __('legal.terms_accounts') }}</p>
                </div>
            </div>
        </div>

        {{-- Prohibited --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center mt-0.5">{{ $n++ }}</span>
                <div class="flex-1">
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">{{ __('legal.terms_prohibited_heading') }}</h2>
                    <p class="text-sm text-gray-500 leading-relaxed mb-4">{{ __('legal.terms_prohibited') }}</p>
                    <ul class="space-y-2.5">
                        @foreach([
                            'legal.terms_prohibited_1',
                            'legal.terms_prohibited_2',
                            'legal.terms_prohibited_3',
                            'legal.terms_prohibited_4',
                        ] as $key)
                        <li class="flex items-start gap-3 text-sm text-gray-500">
                            <svg class="mt-0.5 flex-shrink-0 w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            {{ __($key) }}
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- IP --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center mt-0.5">{{ $n++ }}</span>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">{{ __('legal.terms_ip_heading') }}</h2>
                    <p class="text-sm text-gray-500 leading-relaxed">{{ __('legal.terms_ip') }}</p>
                </div>
            </div>
        </div>

        {{-- Liability --}}
        <div class="bg-amber-50 rounded-2xl border border-amber-100 p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-7 h-7 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mt-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">{{ __('legal.terms_liability_heading') }}</h2>
                    <p class="text-sm text-gray-500 leading-relaxed">{{ __('legal.terms_liability') }}</p>
                </div>
            </div>
        </div>

        {{-- Changes --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center mt-0.5">{{ $n++ }}</span>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">{{ __('legal.terms_changes_heading') }}</h2>
                    <p class="text-sm text-gray-500 leading-relaxed">{{ __('legal.terms_changes') }}</p>
                </div>
            </div>
        </div>

        {{-- Contact --}}
        <div class="bg-indigo-50 rounded-2xl border border-indigo-100 p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center mt-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 mb-1">{{ __('legal.terms_contact_heading') }}</h2>
                    <p class="text-sm text-gray-500 leading-relaxed mb-3">{{ __('legal.terms_contact') }}</p>
                    <a href="mailto:{{ config('mail.from.address') }}"
                       class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">
                        {{ config('mail.from.address') }}
                    </a>
                </div>
            </div>
        </div>

    </div>

</x-layouts.legal>
