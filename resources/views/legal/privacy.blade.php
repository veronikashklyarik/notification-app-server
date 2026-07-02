<x-layouts.legal :title="__('legal.privacy_title')">

    {{-- Hero --}}
    <div class="mb-10">
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">{{ __('legal.privacy_title') }}</h1>
        <p class="mt-2 text-sm text-gray-400">{{ __('legal.privacy_updated') }}</p>
    </div>

    {{-- Sections --}}
    <div class="space-y-4">

        @php $n = 1; @endphp

        {{-- Intro --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center mt-0.5">{{ $n++ }}</span>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">{{ __('legal.privacy_intro_heading') }}</h2>
                    <p class="text-sm text-gray-500 leading-relaxed">{{ __('legal.privacy_intro') }}</p>
                </div>
            </div>
        </div>

        {{-- What we collect --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center mt-0.5">{{ $n++ }}</span>
                <div class="flex-1">
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">{{ __('legal.privacy_collect_heading') }}</h2>
                    <p class="text-sm text-gray-500 leading-relaxed mb-4">{{ __('legal.privacy_collect') }}</p>
                    <ul class="space-y-2.5">
                        @foreach([
                            'legal.privacy_collect_name',
                            'legal.privacy_collect_push',
                            'legal.privacy_collect_timezone',
                            'legal.privacy_collect_google',
                            'legal.privacy_collect_usage',
                        ] as $key)
                        <li class="flex items-start gap-3 text-sm text-gray-500">
                            <span class="mt-1.5 flex-shrink-0 w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                            {{ __($key) }}
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- How we use --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center mt-0.5">{{ $n++ }}</span>
                <div class="flex-1">
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">{{ __('legal.privacy_use_heading') }}</h2>
                    <p class="text-sm text-gray-500 leading-relaxed mb-4">{{ __('legal.privacy_use') }}</p>
                    <ul class="space-y-2.5">
                        @foreach([
                            'legal.privacy_use_1',
                            'legal.privacy_use_2',
                            'legal.privacy_use_3',
                            'legal.privacy_use_4',
                        ] as $key)
                        <li class="flex items-start gap-3 text-sm text-gray-500">
                            <svg class="mt-0.5 flex-shrink-0 w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ __($key) }}
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- Storage & security --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center mt-0.5">{{ $n++ }}</span>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">{{ __('legal.privacy_storage_heading') }}</h2>
                    <p class="text-sm text-gray-500 leading-relaxed">{{ __('legal.privacy_storage') }}</p>
                </div>
            </div>
        </div>

        {{-- Third-party --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center mt-0.5">{{ $n++ }}</span>
                <div class="flex-1">
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">{{ __('legal.privacy_third_party_heading') }}</h2>
                    <p class="text-sm text-gray-500 leading-relaxed mb-4">{{ __('legal.privacy_third_party') }}</p>
                    <ul class="space-y-2.5">
                        @foreach([
                            'legal.privacy_third_party_google',
                            'legal.privacy_third_party_push',
                        ] as $key)
                        <li class="flex items-start gap-3 text-sm text-gray-500">
                            <span class="mt-1.5 flex-shrink-0 w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                            {{ __($key) }}
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- Your rights --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center mt-0.5">{{ $n++ }}</span>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">{{ __('legal.privacy_rights_heading') }}</h2>
                    <p class="text-sm text-gray-500 leading-relaxed">{{ __('legal.privacy_rights') }}</p>
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
                    <h2 class="text-sm font-semibold text-gray-900 mb-1">{{ __('legal.privacy_contact_heading') }}</h2>
                    <p class="text-sm text-gray-500 leading-relaxed mb-3">{{ __('legal.privacy_contact') }}</p>
                    <a href="mailto:{{ config('mail.from.address') }}"
                       class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">
                        {{ config('mail.from.address') }}
                    </a>
                </div>
            </div>
        </div>

    </div>

</x-layouts.legal>
