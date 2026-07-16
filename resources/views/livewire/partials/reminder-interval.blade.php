<div x-data="{ on: $wire.reminderInterval != null }">
    <div class="flex items-center justify-between px-1">
        <span class="text-sm font-semibold text-gray-700">{{ __('Reminder Interval') }}</span>
        <button type="button"
                @click="on = !on; if (on) { $wire.set('reminderInterval', 15) } else { $wire.set('reminderInterval', null) }"
                :class="on ? 'bg-indigo-500' : 'bg-gray-200'"
                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 focus:outline-none">
            <span :class="on ? 'translate-x-5' : 'translate-x-0.5'"
                  class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-200"></span>
        </button>
    </div>
    <div x-show="on" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="px-1 pb-1">
            <select wire:model="reminderInterval" class="input-styled w-full mt-3">
                @foreach(\App\Models\Notification::REMINDER_INTERVALS as $minutes => $label)
                    <option value="{{ $minutes }}">{{ __($label) }}</option>
                @endforeach
            </select>
            <p class="mt-1.5 text-xs text-gray-400">{{ __('Repeat push notifications for pending events at this interval') }}</p>
        </div>
    </div>
</div>
