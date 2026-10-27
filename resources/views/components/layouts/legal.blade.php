{{--
    Shared template for Privacy Policy and Terms of Service: one chip per tab, one card per tab.

    Each tab: ['id' => string, 'label' => string, 'heading' => string,
    'blocks' => list<array{subheading?: string, text?: string, list?: list<string>}>].
    A Contact tab with the support email is always added last. The first tab is open until a chip or #hash picks another.
--}}
@props(['title', 'updated', 'tabs', 'contact', 'otherPage'])

@php
    $supportEmail = config('mail.from.address');
    $tabIds = [...array_column($tabs, 'id'), 'contact'];
    $chips = [...array_map(fn (array $tab): array => ['id' => $tab['id'], 'label' => $tab['label']], $tabs), ['id' => 'contact', 'label' => __('legal.contact_tab')]];
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
    @livewireStyles
</head>
<body class="bg-canvas text-ink antialiased min-h-screen" style="padding-top: env(safe-area-inset-top);">

    <main class="max-w-2xl mx-auto px-5 pt-5" style="padding-bottom: calc(env(safe-area-inset-bottom) + 32px);"
          x-data="{
              ids: @js($tabIds),
              tab: @js($tabIds[0]),
              init() { this.openFromHash(); },
              openFromHash() {
                  const id = window.location.hash.slice(1);
                  if (this.ids.includes(id)) { this.tab = id; }
              },
              open(id) {
                  this.tab = id;
                  history.replaceState(null, '', '#' + id);
              },
          }"
          x-on:hashchange.window="openFromHash()">
        @auth
            <a href="{{ route('settings') }}" class="text-[15px] font-semibold text-text-secondary">‹ {{ __('Settings') }}</a>
        @else
            <a href="{{ route('landing') }}" class="text-[15px] font-semibold text-text-secondary">‹ {{ config('app.name') }}</a>
        @endauth

        <h1 class="mt-3 text-[26px] font-bold tracking-title text-ink">{{ $title }}</h1>
        <p class="mt-1 text-[13px] text-text-tertiary">{{ $updated }}</p>

        <nav class="mt-3.5 flex flex-wrap gap-1.5">
            @foreach ($chips as $chip)
                <a href="#{{ $chip['id'] }}"
                   x-on:click.prevent="open(@js($chip['id']))"
                   class="px-2.5 py-1.5 rounded-pill border bg-white border-border text-[13px] font-semibold text-text-secondary data-active:bg-ink data-active:border-ink data-active:text-white"
                   @if ($loop->first) data-active aria-current="true" @endif
                   x-bind:data-active="tab === @js($chip['id'])"
                   x-bind:aria-current="tab === @js($chip['id']) ? 'true' : 'false'">{{ $chip['label'] }}</a>
            @endforeach
        </nav>

        @foreach ($tabs as $tab)
            <section id="{{ $tab['id'] }}" x-show="tab === @js($tab['id'])" @unless ($loop->first) x-cloak @endunless
                class="mt-4 scroll-mt-4 rounded-card bg-white border border-border px-5 pt-1 pb-6">
                <h2 class="mt-6 text-[17px] font-semibold text-ink">{{ $tab['heading'] }}</h2>

                @foreach ($tab['blocks'] as $block)
                    @isset($block['subheading'])
                        <h3 class="mt-4 text-[15px] font-semibold text-ink">{{ $block['subheading'] }}</h3>
                    @endisset
                    @isset($block['text'])
                        <p class="mt-1.5 text-[15px] leading-[1.6] text-text-secondary">{{ $block['text'] }}</p>
                    @endisset
                    @isset($block['list'])
                        <ul @class([
                            'pl-[18px] list-disc text-[15px] leading-[1.6] text-text-secondary',
                            'mt-2' => isset($tab['blocks'][$loop->index - 1]['text']),
                            'mt-1.5' => ! isset($tab['blocks'][$loop->index - 1]['text']),
                        ])>
                            @foreach ($block['list'] as $item)
                                <li class="mt-1">{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endisset
                @endforeach
            </section>
        @endforeach

        <section id="contact" x-show="tab === 'contact'" x-cloak class="mt-4 scroll-mt-4 rounded-card bg-white border border-border px-5 pt-1 pb-6">
            <h2 class="mt-6 text-[17px] font-semibold text-ink">{{ __('legal.contact_tab') }}</h2>
            <p class="mt-1.5 text-[15px] leading-[1.6] text-text-secondary">
                {{ $contact }}
                <a href="mailto:{{ $supportEmail }}" class="font-semibold text-brand">{{ $supportEmail }}</a>
            </p>
        </section>

        <p class="mt-4 text-center text-[12px] leading-[1.5] text-text-quaternary">
            <a href="{{ route('landing') }}" class="text-text-tertiary">{{ __('Home') }}</a>
            ·
            <a href="{{ $otherPage['url'] }}" class="text-text-tertiary">{{ $otherPage['label'] }}</a>
        </p>
    </main>

    @livewireScripts
</body>
</html>
