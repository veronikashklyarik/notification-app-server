{{--
    Shared template for Privacy Policy and Terms of Service.

    Each section: ['id' => string, 'chip' => ?string, 'heading' => string, 'highlight' => bool,
    'blocks' => list<array{subheading?: string, text?: string, list?: list<string>}>].
    Consecutive plain sections share one white card; a highlighted section gets its own indigo-bordered card.
--}}
@props(['title', 'updated', 'sections', 'contact', 'otherPage'])

@php
    $supportEmail = config('mail.from.address');

    $cards = [];
    foreach ($sections as $section) {
        $highlight = $section['highlight'] ?? false;
        $lastCard = array_key_last($cards);

        if ($highlight || $lastCard === null || $cards[$lastCard]['highlight']) {
            $cards[] = ['highlight' => $highlight, 'sections' => [$section]];
        } else {
            $cards[$lastCard]['sections'][] = $section;
        }
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} — {{ config('app.name') }}</title>

    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas text-ink antialiased min-h-screen" style="padding-top: env(safe-area-inset-top);">

    <main class="max-w-2xl mx-auto px-5 pt-5" style="padding-bottom: calc(env(safe-area-inset-bottom) + 32px);">
        @auth
            <a href="{{ route('settings') }}" class="text-[15px] font-semibold text-text-secondary">‹ {{ __('Settings') }}</a>
        @else
            <a href="{{ route('landing') }}" class="text-[15px] font-semibold text-text-secondary">‹ {{ config('app.name') }}</a>
        @endauth

        <h1 class="mt-3 text-[26px] font-bold tracking-title text-ink">{{ $title }}</h1>
        <p class="mt-1 text-[13px] text-text-tertiary">{{ $updated }}</p>

        <nav class="mt-3.5 flex flex-wrap gap-1.5">
            @foreach ($sections as $section)
                @isset($section['chip'])
                    <a href="#{{ $section['id'] }}" class="px-2.5 py-1.5 rounded-pill bg-white border border-border text-[13px] font-semibold text-text-secondary">{{ $section['chip'] }}</a>
                @endisset
            @endforeach
            <a href="#contact" class="px-2.5 py-1.5 rounded-pill bg-white border border-border text-[13px] font-semibold text-text-secondary">{{ __('legal.chip_contact') }}</a>
        </nav>

        @foreach ($cards as $card)
            <div @class([
                'rounded-card bg-white px-5 pt-1 pb-6',
                'mt-4' => $loop->first,
                'mt-3.5' => ! $loop->first,
                'border-[1.5px] border-brand' => $card['highlight'],
                'border border-border' => ! $card['highlight'],
            ])>
                @foreach ($card['sections'] as $section)
                    <h2 id="{{ $section['id'] }}" class="mt-6 scroll-mt-4 text-[17px] font-semibold text-ink">{{ $section['heading'] }}</h2>

                    @foreach ($section['blocks'] as $block)
                        @isset($block['subheading'])
                            <h3 class="mt-4 text-[15px] font-semibold text-ink">{{ $block['subheading'] }}</h3>
                        @endisset
                        @isset($block['text'])
                            <p class="mt-1.5 text-[15px] leading-[1.6] text-text-secondary">{{ $block['text'] }}</p>
                        @endisset
                        @isset($block['list'])
                            <ul @class([
                                'pl-[18px] list-disc text-[15px] leading-[1.6] text-text-secondary',
                                'mt-2' => isset($section['blocks'][$loop->index - 1]['text']),
                                'mt-1.5' => ! isset($section['blocks'][$loop->index - 1]['text']),
                            ])>
                                @foreach ($block['list'] as $item)
                                    <li class="mt-1">{{ $item }}</li>
                                @endforeach
                            </ul>
                        @endisset
                    @endforeach
                @endforeach
            </div>
        @endforeach

        <div class="mt-3.5 rounded-card bg-white border border-border px-5 pt-1 pb-6">
            <h2 id="contact" class="mt-6 scroll-mt-4 text-[17px] font-semibold text-ink">{{ __('legal.chip_contact') }}</h2>
            <p class="mt-1.5 text-[15px] leading-[1.6] text-text-secondary">
                {{ $contact }}
                <a href="mailto:{{ $supportEmail }}" class="font-semibold text-brand">{{ $supportEmail }}</a>
            </p>
        </div>

        <p class="mt-4 text-center text-[12px] leading-[1.5] text-text-quaternary">
            <a href="{{ route('landing') }}" class="text-text-tertiary">{{ __('Home') }}</a>
            ·
            <a href="{{ $otherPage['url'] }}" class="text-text-tertiary">{{ $otherPage['label'] }}</a>
        </p>
    </main>

</body>
</html>
