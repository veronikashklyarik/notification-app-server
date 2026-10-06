@props([
    'show',
    'title' => null,
    'confirmLabel',
    'cancelLabel' => null,
    'variant' => 'danger',
    'onConfirm',
    'onCancel',
])

@php
    $confirmButtonClass = $variant === 'neutral'
        ? 'bg-ink text-white'
        : 'bg-danger-tint text-danger';
@endphp

<div
    x-show="{{ $show }}"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="{{ $onCancel }}"
    class="fixed inset-0 z-50 flex items-center justify-center p-6"
    style="background: rgba(16,20,40,0.45)"
    @click.self="{{ $onCancel }}"
>
    <div
        x-show="{{ $show }}"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="w-full max-w-[320px] bg-white rounded-[24px] p-6"
    >
        @if($title)
            <h3 class="text-[19px] font-bold text-ink">{{ $title }}</h3>
        @endif
        <div class="mt-2 text-[14px] leading-normal text-text-secondary">
            {{ $slot }}
        </div>
        <div class="mt-[18px] flex gap-2">
            <button type="button" @click="{{ $onCancel }}"
                    class="flex-1 h-12 text-[15px] font-semibold text-text-secondary bg-white border border-border rounded-control">
                {{ $cancelLabel ?? __('Cancel') }}
            </button>
            <button type="button" @click="{{ $onConfirm }}"
                    class="flex-1 h-12 text-[15px] font-semibold rounded-control {{ $confirmButtonClass }}">
                {{ $confirmLabel }}
            </button>
        </div>
    </div>
</div>
