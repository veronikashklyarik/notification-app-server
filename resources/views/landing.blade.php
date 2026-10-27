@php
    $supportEmail = config('mail.from.address');
    $wrap = 'max-w-[1120px] mx-auto px-[clamp(20px,5vw,48px)]';
    $section = $wrap.' pt-[clamp(48px,7vw,88px)]';
    $cta = 'h-[52px] w-full md:w-auto px-[22px] rounded-field text-[16px] font-semibold flex md:inline-flex items-center justify-center gap-2.5';
    $eyebrow = 'text-[12px] font-semibold uppercase tracking-label text-brand';
    $heading = 'mt-1.5 md:mt-2 text-[26px] md:text-[clamp(26px,3.4vw,36px)] font-bold tracking-title leading-[1.2] md:leading-[1.15] text-balance md:text-wrap';
    $cards = 'mt-[18px] md:mt-6 flex flex-col gap-2.5 md:grid md:grid-cols-[repeat(auto-fit,minmax(240px,1fr))] md:gap-3';
    $card = 'rounded-card bg-white border border-border p-[18px] md:p-5 flex md:block gap-3.5 items-start';
    $cardTitle = 'text-[16px] md:text-[17px] font-semibold leading-[1.3] md:mt-3.5';
    $cardText = 'mt-1 md:mt-1.5 text-[14px] md:text-[15px] leading-[1.5] md:leading-[1.55] text-text-secondary';
    $locales = [\App\Enums\Locale::En, \App\Enums\Locale::Pl, \App\Enums\Locale::Ru];

    $steps = [
        ['landing.step_add_title', 'landing.step_add_text', 'landing.step_add_text_short'],
        ['landing.step_push_title', 'landing.step_push_text', 'landing.step_push_text_short'],
        ['landing.step_mark_title', 'landing.step_mark_text', 'landing.step_mark_text_short'],
    ];

    $features = [
        ['M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5', 'landing.feature_schedules_title', 'landing.feature_schedules_text', 'landing.feature_schedules_text'],
        ['M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0', 'landing.feature_push_title', 'landing.feature_push_text', 'landing.feature_push_text_short'],
        ['M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'landing.feature_history_title', 'landing.feature_history_text', 'landing.feature_history_text'],
        ['M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3', 'landing.feature_install_title', 'landing.feature_install_text', 'landing.feature_install_text_short'],
    ];

    $dataPoints = [
        ['landing.data_google_title', 'landing.data_google_text', 'landing.data_google_text_short'],
        ['landing.data_access_title', 'landing.data_access_text', 'landing.data_access_text'],
        ['landing.data_stored_title', 'landing.data_stored_text', 'landing.data_stored_text_short'],
        ['landing.data_leave_title', 'landing.data_leave_text', 'landing.data_leave_text'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <title>{{ __('landing.meta_title') }}</title>
    <meta name="description" content="{{ __('landing.meta_description') }}">
    <link rel="canonical" href="{{ route('landing') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ __('landing.meta_title') }}">
    <meta property="og:description" content="{{ __('landing.meta_description') }}">
    <meta property="og:url" content="{{ route('landing') }}">
    <meta property="og:image" content="{{ asset('icons/icon-512.png') }}">
    <meta name="twitter:card" content="summary">

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css'])
</head>
<body class="bg-canvas text-ink antialiased leading-[normal]" style="padding-top: env(safe-area-inset-top);">

    <header class="bg-canvas/92 border-b border-border">
        <div class="{{ $wrap }} h-[60px] md:h-auto md:min-h-16 md:py-3 flex md:flex-wrap items-center justify-between gap-x-6 gap-y-3">
            <a href="{{ route('landing') }}" class="flex items-center gap-2.5">
                <span class="w-8 h-8 md:w-[34px] md:h-[34px] rounded-[10px] md:rounded-[11px] bg-brand flex items-center justify-center">
                    <svg class="w-[18px] h-[18px] md:w-[19px] md:h-[19px]" fill="none" stroke="#fff" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </span>
                <span class="text-[17px] md:text-[18px] font-bold tracking-title text-ink">{{ config('app.name') }}</span>
            </a>
            <nav class="flex-1 flex flex-wrap items-center justify-end gap-[22px]">
                <a href="#how" class="hidden md:inline text-[14px] font-semibold text-text-secondary">{{ __('landing.nav_how') }}</a>
                <a href="#data" class="hidden md:inline text-[14px] font-semibold text-text-secondary">{{ __('landing.nav_data') }}</a>
                <a href="{{ route('login') }}" class="h-11 md:h-auto flex items-center text-[15px] md:text-[14px] font-semibold text-brand">{{ __('Sign in') }}</a>
            </nav>
        </div>
    </header>

    <main>

        {{-- Hero --}}
        <section class="{{ $wrap }} pt-8 md:pt-[clamp(40px,6vw,80px)] md:flex md:flex-wrap md:items-center md:gap-12">
            <div class="min-w-0 md:flex-[1_1_420px]">
                <p class="{{ $eyebrow }}">{{ __('landing.hero_eyebrow') }}</p>
                <h1 class="mt-2 md:mt-2.5 text-[34px] md:text-[clamp(36px,5.4vw,60px)] font-bold tracking-[-0.03em] leading-[1.1] md:leading-[1.05] text-balance">{{ __('landing.hero_title') }}</h1>
                <p class="mt-3.5 md:mt-[18px] md:max-w-[560px] text-[16px] md:text-[clamp(16px,1.6vw,19px)] leading-[1.55] text-text-secondary text-pretty">{{ __('landing.hero_text') }}</p>
                <div class="mt-6 md:mt-7 flex flex-col md:flex-row md:flex-wrap gap-2.5">
                    <a href="{{ route('register') }}" class="{{ $cta }} bg-brand text-white">{{ __('landing.cta_create') }}</a>
                    <a href="{{ route('auth.google') }}" data-google-auth class="{{ $cta }} bg-white border border-border text-ink">
                        <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        {{ __('Continue with Google') }}
                    </a>
                </div>
                <p class="mt-3 md:mt-3.5 text-[13px] leading-[1.5] text-center md:text-left text-text-tertiary">{{ __('landing.hero_note_lead') }}<br class="md:hidden"> {{ __('landing.hero_note_platforms') }}</p>
            </div>

            {{-- Today screen mockup --}}
            <div class="mt-8 md:mt-0 md:flex-[0_1_300px] flex justify-center">
                <div class="w-[250px] md:w-[260px] shrink-0 rounded-[32px] md:rounded-[34px] bg-canvas border border-border-strong shadow-[0_28px_60px_-34px_rgba(16,20,40,.45)] px-3.5 pt-[26px] pb-4 md:pt-7 md:pb-[18px]" aria-hidden="true">
                    <p class="text-[10px] font-semibold uppercase tracking-label text-text-tertiary">{{ __('landing.mock_date') }}</p>
                    <p class="text-[20px] font-bold tracking-title">{{ __('Today') }}</p>
                    <div class="mt-3 rounded-field bg-ink p-3.5">
                        <p class="text-[9px] font-semibold uppercase tracking-caption text-[#8f96a3]">{{ __('landing.mock_next_up') }}</p>
                        <p class="mt-1.5 text-white"><b class="text-[24px] tabular-nums">14:30</b> <span class="text-[14px] font-semibold">{{ __('landing.mock_stretching') }}</span></p>
                        <div class="mt-2.5 flex gap-1.5">
                            <span class="flex-1 h-8 rounded-pill bg-white text-[12px] font-semibold flex items-center justify-center">{{ __('Done') }}</span>
                            <span class="w-[70px] h-8 rounded-pill bg-ink-muted text-[#d5d9e0] text-[12px] font-semibold flex items-center justify-center">{{ __('Skip') }}</span>
                        </div>
                    </div>
                    <div class="mt-2.5 flex flex-col gap-1.5">
                        <div class="rounded-control border border-border bg-surface-muted px-3 py-2.5 flex items-center gap-2.5 text-[13px]">
                            <span class="w-[38px] font-semibold tabular-nums text-text-secondary">08:00</span>
                            <span class="text-text-secondary line-through">{{ __('landing.mock_vitamin') }}</span>
                            <span class="ml-auto w-[22px] h-[22px] rounded-full bg-success"></span>
                        </div>
                        <div class="rounded-control border border-border bg-white px-3 py-2.5 flex items-center gap-2.5 text-[13px]">
                            <span class="w-[38px] font-semibold tabular-nums">20:00</span>
                            <span>{{ __('landing.mock_antibiotics') }}</span>
                            <span class="ml-auto w-[22px] h-[22px] rounded-full border-[1.5px] border-border-input"></span>
                        </div>
                        <div class="rounded-control border border-border bg-white px-3 py-2.5 flex items-center gap-2.5 text-[13px]">
                            <span class="w-[38px] font-semibold tabular-nums">21:30</span>
                            <span>{{ __('landing.mock_reading') }}</span>
                            <span class="ml-auto w-[22px] h-[22px] rounded-full border-[1.5px] border-border-input"></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- How it works --}}
        <section id="how" class="{{ $section }}">
            <p class="{{ $eyebrow }}">{{ __('landing.nav_how') }}</p>
            <h2 class="{{ $heading }}">{{ __('landing.how_title') }}</h2>
            <div class="{{ $cards }}">
                @foreach ($steps as [$title, $text, $shortText])
                    <div class="{{ $card }}">
                        <span class="w-7 h-7 shrink-0 rounded-full bg-ink text-white text-[13px] font-semibold flex items-center justify-center">{{ $loop->iteration }}</span>
                        <div>
                            <h3 class="{{ $cardTitle }}">{{ __($title) }}</h3>
                            <p class="{{ $cardText }}"><span class="md:hidden">{{ __($shortText) }}</span><span class="hidden md:inline">{{ __($text) }}</span></p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- What's inside --}}
        <section class="{{ $section }}">
            <p class="{{ $eyebrow }}">{{ __('landing.inside_eyebrow') }}</p>
            <h2 class="{{ $heading }}">{{ __('landing.inside_title') }}</h2>
            <div class="{{ $cards }}">
                @foreach ($features as [$iconPath, $title, $text, $shortText])
                    <div class="{{ $card }}">
                        <span class="w-10 h-10 shrink-0 rounded-chip bg-indigo-tint flex items-center justify-center">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#4f46e5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $iconPath }}" /></svg>
                        </span>
                        <div>
                            <h3 class="{{ $cardTitle }}">{{ __($title) }}</h3>
                            <p class="{{ $cardText }}"><span class="md:hidden">{{ __($shortText) }}</span><span class="hidden md:inline">{{ __($text) }}</span></p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Your data --}}
        <section id="data" class="{{ $section }}">
            <div class="rounded-hero md:rounded-[24px] bg-white border border-border px-5 pt-[22px] pb-2 md:p-[clamp(22px,4vw,40px)] md:flex md:flex-wrap md:gap-8">
                <div class="md:flex-[1_1_280px]">
                    <p class="{{ $eyebrow }}">{{ __('landing.nav_data') }}</p>
                    <h2 class="{{ $heading }}">{{ __('landing.data_title') }}</h2>
                    <p class="mt-2.5 md:mt-3 text-[14px] md:text-[15px] leading-[1.55] text-text-secondary"><span class="md:hidden">{{ __('landing.data_intro_short') }}</span><span class="hidden md:inline">{{ __('landing.data_intro') }}</span></p>
                    <a href="{{ route('legal.privacy') }}" class="hidden md:inline-block mt-4 text-[15px] font-semibold text-brand">{{ __('landing.data_privacy_link') }}</a>
                </div>
                <div class="mt-2.5 md:mt-0 md:flex-[1.4_1_340px]">
                    @foreach ($dataPoints as [$title, $text, $shortText])
                        <div class="flex items-start gap-3 py-3.5 border-t border-border-light md:first:border-t-0">
                            <span class="mt-px w-[22px] h-[22px] shrink-0 rounded-full bg-indigo-tint flex items-center justify-center">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="#4f46e5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 12.75l6 6 9-13.5" /></svg>
                            </span>
                            <p class="text-[14px] md:text-[15px] leading-[1.55] text-text-secondary">
                                <b class="font-semibold text-ink">{{ __($title) }}</b>
                                <span class="md:hidden">{{ __($shortText) }}</span><span class="hidden md:inline">{{ __($text) }}</span>
                            </p>
                        </div>
                    @endforeach
                </div>
                <a href="{{ route('legal.privacy') }}" class="md:hidden h-[52px] flex items-center border-t border-border-light text-[15px] font-semibold text-brand">{{ __('landing.data_privacy_link') }}</a>
            </div>
        </section>

        {{-- Closing call to action: dark card on phones, open row on wider screens --}}
        <section class="md:hidden px-5 pt-12">
            <div class="rounded-hero bg-ink px-5 pt-7 pb-5 text-center">
                <h2 class="text-[24px] font-bold tracking-title text-white">{{ __('landing.final_title') }}</h2>
                <p class="mt-1.5 text-[15px] text-[#a9b0bc]">{{ __('landing.final_text') }}</p>
                <div class="mt-5 flex flex-col gap-2.5">
                    <a href="{{ route('register') }}" class="{{ $cta }} bg-white text-ink">{{ __('landing.cta_create') }}</a>
                    <a href="{{ route('login') }}" class="{{ $cta }} bg-ink-muted text-[#d5d9e0]">{{ __('Sign in') }}</a>
                </div>
            </div>
        </section>
        <section class="hidden md:block {{ $section }} text-center">
            <h2 class="text-[clamp(26px,3.4vw,36px)] font-bold tracking-title leading-[1.15]">{{ __('landing.final_title') }}</h2>
            <p class="mt-2.5 text-[16px] text-text-secondary">{{ __('landing.final_text') }}</p>
            <div class="mt-[22px] flex flex-wrap justify-center gap-2.5">
                <a href="{{ route('register') }}" class="{{ $cta }} bg-brand text-white">{{ __('landing.cta_create') }}</a>
                <a href="{{ route('login') }}" class="{{ $cta }} bg-white border border-border text-ink">{{ __('Sign in') }}</a>
            </div>
        </section>

    </main>

    {{-- Footer: stacked tap targets on phones, one row on wider screens --}}
    <footer class="md:hidden mt-12 px-5 pt-6 border-t border-border bg-white flex flex-col gap-1" style="padding-bottom: calc(env(safe-area-inset-bottom) + 36px);">
        <a href="{{ route('legal.privacy') }}" class="h-10 flex items-center text-[15px] font-semibold text-ink">{{ __('Privacy Policy') }}</a>
        <a href="{{ route('legal.terms') }}" class="h-10 flex items-center text-[15px] font-semibold text-ink">{{ __('Terms of Service') }}</a>
        <p class="mt-3 text-[13px] text-text-tertiary">
            @foreach ($locales as $locale)
                @if ($locale->value === app()->getLocale())
                    {{ $locale->label() }}
                @else
                    <a href="{{ route('locale.switch', $locale) }}" lang="{{ $locale->value }}" class="text-text-tertiary">{{ $locale->label() }}</a>
                @endif
                @unless ($loop->last) · @endunless
            @endforeach
        </p>
        <p class="mt-1.5 text-[13px] text-text-tertiary">&copy; {{ date('Y') }} {{ config('app.name') }} · <a href="mailto:{{ $supportEmail }}" class="text-text-secondary">{{ $supportEmail }}</a></p>
    </footer>
    <footer class="hidden md:block mt-[clamp(48px,7vw,88px)] border-t border-border bg-white">
        <div class="{{ $wrap }} pt-6 pb-8 flex flex-wrap items-center justify-between gap-4">
            <p class="text-[13px] text-text-tertiary">&copy; {{ date('Y') }} {{ config('app.name') }} · <a href="mailto:{{ $supportEmail }}" class="text-text-secondary">{{ $supportEmail }}</a></p>
            <nav class="flex flex-wrap gap-[18px] text-[13px]">
                <a href="{{ route('legal.privacy') }}" class="font-semibold text-text-secondary">{{ __('Privacy Policy') }}</a>
                <a href="{{ route('legal.terms') }}" class="font-semibold text-text-secondary">{{ __('Terms of Service') }}</a>
                <span class="text-text-tertiary">
                    @foreach ($locales as $locale)
                        @if ($locale->value === app()->getLocale())
                            {{ $locale->label() }}
                        @else
                            <a href="{{ route('locale.switch', $locale) }}" lang="{{ $locale->value }}" class="text-text-tertiary">{{ $locale->label() }}</a>
                        @endif
                        @unless ($loop->last) · @endunless
                    @endforeach
                </span>
            </nav>
        </div>
    </footer>

    <script>
        {{-- Same as the Sign in page: pass the browser time zone so a new Google account starts in it. --}}
        document.querySelectorAll('[data-google-auth]').forEach((link) => {
            link.href += '?timezone=' + encodeURIComponent(Intl.DateTimeFormat().resolvedOptions().timeZone);
        });
    </script>

</body>
</html>
