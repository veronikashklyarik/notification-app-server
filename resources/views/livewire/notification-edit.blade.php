<x-sheet :title="__('Edit reminder')" :back-url="$backUrl" :on-close="$embedded ? '$wire.closeEmbedded()' : null">
    @include('livewire.partials.schedule-sheet-form')
</x-sheet>
