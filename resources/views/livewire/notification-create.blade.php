<div class="p-4 animate-slide-up">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ $backUrl }}" class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900">{{ __('New Reminder') }}</h1>
    </div>

    @if($errors->any())
        <div class="p-4 mb-6 text-sm text-red-600 bg-red-50/80 rounded-2xl border border-red-100">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form wire:submit="save" class="space-y-5">
        <div>
            <label for="name" class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Name') }}</label>
            <input type="text" id="name" wire:model="name" required
                class="input-styled w-full" placeholder="{{ __('e.g. Take vitamins') }}">
        </div>

        <div>
            <label for="description" class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Description') }} <span class="text-gray-300">({{ __('optional') }})</span></label>
            <textarea id="description" wire:model="description" rows="2"
                class="input-styled w-full resize-none" placeholder="{{ __('Add details...') }}"></textarea>
        </div>

        <div>
            <label class="block mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Schedule') }}</label>
            <div class="grid grid-cols-2 gap-2">
                @foreach([
                    'every_day' => ['Every Day', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'week_days' => ['Specific Days', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    'every_n_days' => ['Every N Days', 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
                    'cyclical' => ['Cyclical', 'M12 4v16m8-8H4'],
                    'as_needed' => ['As Needed', 'M13 10V3L4 14h7v7l9-11h-7z'],
                ] as $value => [$label, $icon])
                    <label class="relative cursor-pointer @if($value === 'as_needed') col-span-2 @endif">
                        <input type="radio" wire:model.live="schedule_type" value="{{ $value }}" class="peer sr-only">
                        <div class="flex items-center gap-2.5 p-3 rounded-xl border-2 transition-all
                            peer-checked:border-indigo-500 peer-checked:bg-indigo-50/50 peer-checked:shadow-sm
                            border-gray-200 hover:border-gray-300">
                            <svg class="w-4 h-4 shrink-0 text-gray-400 peer-checked:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
                            </svg>
                            <span class="text-sm font-medium text-gray-700">{{ __($label) }}</span>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        @if($schedule_type === 'week_days')
            <div>
                <label class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Days of Week') }}</label>
                <div class="flex gap-1.5">
                    @php $dayLabels = [1 => 'Mo', 2 => 'Tu', 3 => 'We', 4 => 'Th', 5 => 'Fr', 6 => 'Sa', 7 => 'Su']; @endphp
                    @foreach($dayLabels as $day => $label)
                        <label class="relative flex-1">
                            <input type="checkbox" wire:model="week_days" value="{{ $day }}" class="peer sr-only">
                            <span class="flex items-center justify-center py-2.5 text-xs font-bold border-2 rounded-xl cursor-pointer transition-all
                                peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 peer-checked:shadow-sm
                                text-gray-500 border-gray-200 hover:border-gray-300">
                                {{ __($label) }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        @if($schedule_type === 'every_n_days')
            <div>
                <label class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Repeat every') }}</label>
                <div class="flex items-center gap-2">
                    <input type="number" wire:model="every_n_days" min="1" max="365"
                        class="input-styled w-24 text-center">
                    <span class="text-sm font-medium text-gray-500">{{ __('days') }}</span>
                </div>
            </div>
        @endif

        @if($schedule_type === 'cyclical')
            <div>
                <label class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Repeat every') }}</label>
                <div class="flex items-center gap-2">
                    <input type="number" wire:model="cyclical_value" min="1"
                        class="input-styled w-24 text-center">
                    <select wire:model="cyclical_unit" class="input-styled flex-1">
                        @foreach(['days' => 'Days', 'weeks' => 'Weeks', 'months' => 'Months', 'years' => 'Years'] as $val => $lbl)
                            <option value="{{ $val }}">{{ __($lbl) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        @if($schedule_type !== 'as_needed')
            <div>
                <label class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Times') }}</label>
                @foreach($times as $index => $time)
                    <div class="flex items-center gap-2 mb-2">
                        <input type="time" wire:model="times.{{ $index }}" class="input-styled flex-1">
                        @if(count($times) > 1)
                            <button type="button" wire:click="removeTime({{ $index }})"
                                class="w-10 h-10 flex items-center justify-center rounded-xl text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </div>
                @endforeach
                <button type="button" wire:click="addTime" class="flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-500 mt-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('Add time') }}
                </button>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="starts_at" class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Start Date') }}</label>
                <input type="date" id="starts_at" wire:model="starts_at" lang="{{ app()->getLocale() }}" class="input-styled w-full">
            </div>
            <div>
                <label for="ends_at" class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('End Date') }}</label>
                <input type="date" id="ends_at" wire:model="ends_at" lang="{{ app()->getLocale() }}" class="input-styled w-full">
            </div>
        </div>

        <label class="flex items-center justify-between p-4 bg-white border-2 border-gray-200 rounded-xl cursor-pointer hover:border-gray-300 transition-colors">
            <span class="text-sm font-semibold text-gray-700">{{ __('Active') }}</span>
            <input type="checkbox" wire:model="is_active"
                class="w-5 h-5 text-indigo-600 rounded-md border-gray-300 focus:ring-indigo-500">
        </label>

        <button type="submit" class="btn-primary w-full py-3.5 text-sm mt-2" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">{{ __('Create Reminder') }}</span>
            <span wire:loading wire:target="save">{{ __('Creating...') }}</span>
        </button>
    </form>
</div>
