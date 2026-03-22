<x-layouts.app title="Edit Notification">

    <div class="mb-8">
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('notifications.index') }}" class="hover:text-indigo-600 transition-colors">Notifications</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
            <a href="{{ route('notifications.show', $notification) }}" class="hover:text-indigo-600 transition-colors truncate max-w-xs">{{ $notification->name }}</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
            <span class="text-gray-900">Edit</span>
        </nav>
        <h1 class="text-2xl font-semibold text-gray-900">Edit notification</h1>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 p-6 max-w-2xl">
        <form method="POST" action="{{ route('notifications.update', $notification) }}" class="space-y-6"
              x-data="notificationForm(
                  '{{ old('schedule_type', $notification->schedule_type->value) }}',
                  {!! \Illuminate\Support\Js::from(old('times', $notification->times ?? ['09:00'])) !!},
                  {!! \Illuminate\Support\Js::from(array_map('intval', (array) old('week_days', $notification->week_days ?? []))) !!},
                  {{ old('every_n_days', $notification->every_n_days ?? 1) }},
                  {{ old('cyclical_value', $notification->cyclical_value ?? 1) }},
                  '{{ old('cyclical_unit', $notification->cyclical_unit ?? 'weeks') }}'
              )">
            @csrf
            @method('PUT')

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $notification->name) }}" required
                       class="w-full px-3.5 py-2.5 text-sm rounded-lg border transition-colors outline-none
                              {{ $errors->has('name') ? 'border-red-300 bg-red-50' : 'border-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100' }}">
                @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea id="description" name="description" rows="2"
                          class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-gray-300 transition-colors outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 resize-none">{{ old('description', $notification->description) }}</textarea>
            </div>

            {{-- Schedule Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Schedule <span class="text-red-500">*</span></label>
                <div class="flex flex-wrap gap-2 mb-5">
                    @foreach(\App\Enums\ScheduleType::cases() as $type)
                        <label class="cursor-pointer">
                            <input type="radio" name="schedule_type" value="{{ $type->value }}"
                                   x-model="scheduleType" class="sr-only peer">
                            <span class="inline-flex items-center px-3.5 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 transition-all
                                         peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-700 peer-checked:font-medium
                                         hover:border-gray-300 hover:bg-gray-50 cursor-pointer">
                                {{ $type->label() }}
                            </span>
                        </label>
                    @endforeach
                </div>

                {{-- Specific days of the week --}}
                <div x-show="scheduleType === 'week_days'" x-cloak class="space-y-2">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Days of the week</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'] as $num => $label)
                            <button type="button" @click="toggleDay({{ $num }})"
                                    :class="isDaySelected({{ $num }}) ? 'bg-indigo-50 border-indigo-500 text-indigo-700 font-medium' : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
                                    class="px-3 py-1.5 text-sm rounded-lg border transition-all">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    <template x-for="day in weekDays" :key="day">
                        <input type="hidden" name="week_days[]" :value="day">
                    </template>
                    @error('week_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Every N days --}}
                <div x-show="scheduleType === 'every_n_days'" x-cloak class="flex items-center gap-3">
                    <span class="text-sm text-gray-600 flex-shrink-0">Every</span>
                    <input type="number" name="every_n_days" min="1" max="365" x-model="everyNDays"
                           class="w-20 px-3 py-2.5 text-sm rounded-lg border border-gray-300 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                    <span class="text-sm text-gray-600">days</span>
                </div>

                {{-- Cyclical --}}
                <div x-show="scheduleType === 'cyclical'" x-cloak class="flex items-center gap-3">
                    <span class="text-sm text-gray-600 flex-shrink-0">Every</span>
                    <input type="number" name="cyclical_value" min="1" x-model="cyclicalValue"
                           class="w-20 px-3 py-2.5 text-sm rounded-lg border border-gray-300 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                    <select name="cyclical_unit" x-model="cyclicalUnit"
                            class="flex-1 px-3.5 py-2.5 text-sm rounded-lg border border-gray-300 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white">
                        <option value="days">Days</option>
                        <option value="weeks">Weeks</option>
                        <option value="months">Months</option>
                        <option value="years">Years</option>
                    </select>
                </div>
            </div>

            {{-- Times --}}
            <div x-show="scheduleType !== 'as_needed'" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-2">Times</label>
                <div class="space-y-2">
                    <template x-for="(time, idx) in times" :key="idx">
                        <div class="flex items-center gap-2">
                            <input type="time" name="times[]" x-model="times[idx]"
                                   class="px-3 py-2.5 text-sm rounded-lg border border-gray-300 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                            <button type="button" @click="removeTime(idx)" x-show="times.length > 1"
                                    class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addTime()"
                        class="mt-2 flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add another time
                </button>
            </div>

            {{-- Duration --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-1.5">Start Date</label>
                    <input type="date" id="starts_at" name="starts_at"
                           value="{{ old('starts_at', $notification->starts_at?->format('Y-m-d')) }}"
                           class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-gray-300 transition-colors outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                </div>
                <div>
                    <label for="ends_at" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Ends on
                        <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <input type="date" id="ends_at" name="ends_at"
                           value="{{ old('ends_at', $notification->ends_at?->format('Y-m-d')) }}"
                           class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-gray-300 transition-colors outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                </div>
            </div>

            {{-- Active Toggle --}}
            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                       class="w-4 h-4 rounded border-gray-300 accent-indigo-600"
                       {{ old('is_active', $notification->is_active) ? 'checked' : '' }}>
                <label for="is_active" class="text-sm font-medium text-gray-700">Active</label>
                <span class="text-xs text-gray-400">Uncheck to pause this notification</span>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Save changes
                </button>
                <a href="{{ route('notifications.show', $notification) }}"
                   class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    @include('notifications._form_script')

</x-layouts.app>
