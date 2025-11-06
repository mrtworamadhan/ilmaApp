<x-filament-panels::page>
    <form wire:submit.prevent="saveAttendance">
        {{ $this->form }}
    </form>
</x-filament-panels::page>
