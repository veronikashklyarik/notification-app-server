<div x-data="{
    newDate: '',
    addFromPicker() {
        if (this.newDate) { $wire.addSpecificDate(this.newDate); this.newDate = ''; }
    },
}">
    <div>
        @forelse($specific_dates as $index => $entry)
            @php
                $dateStr = is_array($entry) ? ($entry['date'] ?? '') : $entry;
                $dateTimes = is_array($entry) ? ($entry['times'] ?? ['09:00']) : ['09:00'];
            @endphp
            <div class="flex items-start gap-2 py-2 {{ !$loop->first ? 'border-t border-border-light' : '' }}" wire:key="date-{{ $dateStr }}">
                <span class="w-[92px] shrink-0 h-9 flex items-center text-[14px] font-semibold text-ink">
                    {{ $dateStr ? \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $dateStr)->translatedFormat('D, j M') : '' }}
                </span>
                <div class="flex flex-wrap items-center gap-2 flex-1">
                    @foreach($dateTimes as $timeIndex => $time)
                        <div class="flex items-center gap-1 h-9 pl-3 pr-1.5 rounded-chip bg-canvas-sunken border {{ $errors->has("specific_dates.$index.times.$timeIndex") ? 'border-danger' : 'border-border' }}" wire:key="date-{{ $dateStr }}-time-{{ $timeIndex }}">
                            <input type="time" wire:model.live="specific_dates.{{ $index }}.times.{{ $timeIndex }}" class="w-[58px] text-[14px] font-medium text-ink bg-transparent outline-none">
                            @if(count($dateTimes) > 1)
                                <button type="button" wire:click="removeTimeFromDate({{ $index }}, {{ $timeIndex }})" class="w-5 h-5 rounded-full flex items-center justify-center text-text-tertiary" aria-label="{{ __('Remove time') }}">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" stroke-linecap="round">
                                        <path d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endforeach
                    <button type="button" wire:click="addTimeToDate({{ $index }})"
                            class="w-11 h-11 shrink-0 flex items-center justify-center text-[20px] leading-none text-brand" aria-label="{{ __('Add time on :date', ['date' => $dateStr ? \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $dateStr)->translatedFormat('j M') : '']) }}">+</button>
                </div>
                <button type="button" wire:click="removeSpecificDate({{ $index }})" class="w-8 h-9 shrink-0 flex items-center justify-center text-text-tertiary" aria-label="{{ __('Remove :date', ['date' => $dateStr ? \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $dateStr)->translatedFormat('j M') : '']) }}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" stroke-linecap="round">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            @foreach($dateTimes as $timeIndex => $time)
                @error("specific_dates.$index.times.$timeIndex")
                    <p class="-mt-1 mb-1 pl-[100px] text-[12px] text-danger">{{ $message }}</p>
                @enderror
            @endforeach
        @empty
            @error('specific_dates')
                <p class="px-1 text-[13px] text-danger">{{ $message }}</p>
            @enderror
            @unless($errors->has('specific_dates'))
                <p class="px-1 text-[13px] text-text-quaternary">{{ __('No dates added yet') }}</p>
            @endunless
        @endforelse
    </div>

    <div class="relative mt-2">
        <input type="date" x-model="newDate" x-on:change="addFromPicker()" lang="{{ app()->getLocale() }}"
               :min="new Date().toISOString().split('T')[0]"
               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" aria-label="{{ __('+ Add a date') }}">
        <div class="w-full h-11 rounded-field border border-dashed border-border-dashed text-[15px] font-semibold text-brand flex items-center justify-center pointer-events-none">
            {{ __('+ Add a date') }}
        </div>
    </div>
</div>
