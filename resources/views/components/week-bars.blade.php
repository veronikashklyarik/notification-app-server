@props(['bars', 'keyPrefix', 'selectedDay' => null, 'selectable' => false])
@php
    // One scale for every week chart, so the same day reads the same on Today and in History
    $chartHeight = 64;
    $emptyHeight = 4;
    $missedHeight = 16;
@endphp
<div class="mt-4 flex items-end gap-2" style="height: {{ $chartHeight }}px">
    @foreach($bars as $bar)
        @php
            $isSelected = $selectedDay === $bar['date'];
            $isDimmed = $selectedDay && ! $isSelected;
            $isRinged = $isSelected || ($bar['isToday'] && ! $selectedDay);
            if ($bar['marks'] === 0) {
                $barHeight = $emptyHeight;
                $barColor = 'bg-avatar-bg';
            } elseif ($bar['ratio'] >= 1.0) {
                $barHeight = $chartHeight;
                $barColor = 'bg-brand';
            } elseif ($bar['ratio'] == 0.0) {
                $barHeight = $missedHeight;
                $barColor = 'bg-warning-bar';
            } else {
                $barHeight = (int) round($missedHeight + $bar['ratio'] * ($chartHeight - $missedHeight));
                $barColor = 'bg-indigo-light-bar';
            }
        @endphp
        @if($selectable)
            <button type="button" wire:click="selectDay('{{ $bar['date'] }}')" wire:key="{{ $keyPrefix }}-{{ $bar['date'] }}"
                    class="flex-1 h-full flex items-end {{ $isDimmed ? 'opacity-40' : '' }}">
                <span class="w-full rounded-tag {{ $barColor }} {{ $isRinged ? 'ring-2 ring-ink ring-offset-1' : '' }}" style="height: {{ $barHeight }}px"></span>
            </button>
        @else
            <a href="{{ route('events.index', ['day' => $bar['date']]) }}" wire:key="{{ $keyPrefix }}-{{ $bar['date'] }}"
               class="flex-1 h-full flex items-end">
                <span class="w-full rounded-tag {{ $barColor }} {{ $isRinged ? 'ring-2 ring-ink ring-offset-1' : '' }}" style="height: {{ $barHeight }}px"></span>
            </a>
        @endif
    @endforeach
</div>
<div class="mt-1.5 flex gap-2">
    @foreach($bars as $bar)
        <p class="flex-1 text-center text-[11px] {{ $bar['isToday'] ? 'font-semibold text-ink' : 'text-text-tertiary' }}">{{ $bar['initial'] }}</p>
    @endforeach
</div>
