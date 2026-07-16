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

        @include('livewire.partials.schedule-fields')

        <div x-data="{ on: $wire.entangle('is_active') }"
             class="flex items-center justify-between px-1">
            <span class="text-sm font-semibold text-gray-700">{{ __('Active') }}</span>
            <button type="button"
                    @click="on = !on"
                    :class="on ? 'bg-indigo-500' : 'bg-gray-200'"
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 focus:outline-none">
                <span :class="on ? 'translate-x-5' : 'translate-x-0.5'"
                      class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-200"></span>
            </button>
        </div>

        @include('livewire.partials.reminder-interval')

        <button type="submit" class="btn-primary w-full py-3.5 text-sm mt-2" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">{{ __('Create Reminder') }}</span>
            <span wire:loading wire:target="save">{{ __('Creating...') }}</span>
        </button>
    </form>
</div>
