{{-- Relies on `pw` and `score` being defined in an ancestor x-data (see register/reset-password/profile). --}}
<div x-show="pw.length > 0" x-cloak class="mt-2">
    <div class="flex gap-1.5">
        <template x-for="i in 3" :key="i">
            <div class="flex-1 h-1 rounded-full"
                 :class="(score <= 1 && i <= 1) ? 'bg-danger' : ((score === 2 && i <= 2) ? 'bg-warning' : ((score >= 3 && i <= 3) ? 'bg-success' : 'bg-fill'))">
            </div>
        </template>
    </div>
    <ul class="mt-2 space-y-1">
        @foreach([
            ['check' => 'pw.length >= 8', 'label' => __('At least 8 characters')],
            ['check' => '/[a-zA-Z]/.test(pw)', 'label' => __('A letter')],
            ['check' => '/[0-9]/.test(pw)', 'label' => __('A number')],
        ] as $criterion)
            <li class="flex items-center gap-1.5 text-[12px]" :class="{{ $criterion['check'] }} ? 'text-success' : 'text-text-tertiary'">
                <span class="w-3.5 h-3.5 shrink-0 rounded-full flex items-center justify-center" :class="{{ $criterion['check'] }} ? 'bg-success' : 'border-[1.5px] border-border-input'">
                    <svg x-show="{{ $criterion['check'] }}" class="w-2 h-2 text-white" fill="none" viewBox="0 0 24 24" stroke-width="4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </span>
                {{ $criterion['label'] }}
            </li>
        @endforeach
    </ul>
</div>
