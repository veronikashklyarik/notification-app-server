@props([
    'name' => null,
    'id' => null,
    'placeholder' => '••••••••',
    'autocomplete' => 'current-password',
    'hasError' => false,
])

<div x-data="{ visible: false }"
     class="flex items-center gap-2.5 h-12 px-4 rounded-field border bg-white focus-within:border-brand focus-within:ring-[3px] focus-within:ring-brand/10 {{ $hasError ? 'border-danger' : 'border-border' }}">
    <input
        type="password"
        :type="visible ? 'text' : 'password'"
        @if($name) name="{{ $name }}" @endif
        @if($id ?? $name) id="{{ $id ?? $name }}" @endif
        placeholder="{{ $placeholder }}"
        autocomplete="{{ $autocomplete }}"
        {{ $attributes->except(['class']) }}
        class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[15px] text-ink placeholder:text-text-quaternary focus:outline-none focus:ring-0"
    >
    <button
        type="button"
        @click="visible = !visible"
        class="shrink-0 text-[13px] font-semibold text-brand"
    >
        <span x-show="!visible">{{ __('Show') }}</span>
        <span x-show="visible" x-cloak>{{ __('Hide') }}</span>
    </button>
</div>
