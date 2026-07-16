<div x-data="{ open: $wire.reminderInterval != null }" class="rounded-xl border-2 border-gray-200 bg-white overflow-hidden">
    <button type="button"
        x-show="!open"
        @click="open = true"
        class="w-full flex items-center justify-between p-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
        <span>{{ __('Reminder Interval') }}</span>
        <span class="text-xs font-normal text-gray-400">{{ __('No reminders') }} &mdash; <span class="text-indigo-500">{{ __('Add') }}</span></span>
    </button>

    <div x-show="open" x-cloak>
        <div class="px-4 pt-4 pb-3">
            <label for="reminderInterval" class="block mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Reminder Interval') }}</label>
            <select id="reminderInterval" wire:model="reminderInterval" class="input-styled w-full">
                @foreach(\App\Models\Notification::REMINDER_INTERVALS as $minutes => $label)
                    <option value="{{ $minutes }}">{{ __($label) }}</option>
                @endforeach
            </select>
            <p class="mt-1.5 text-xs text-gray-400">{{ __('Repeat push notifications for pending events at this interval') }}</p>
        </div>
        <div class="px-4 pb-3">
            <button type="button"
                @click="open = false; $wire.set('reminderInterval', null)"
                class="text-xs text-gray-400 hover:text-red-500 transition-colors">
                &times; {{ __('Remove reminder') }}
            </button>
        </div>
    </div>
</div>
