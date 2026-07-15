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
