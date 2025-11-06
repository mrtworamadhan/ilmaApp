<x-filament-panels::page>

    <form wire:submit>
        {{ $this->form }}
    </form>
 
    <x-filament::section class="mt-6">
        {{ $this->table }}
    </x-filament::section>
 
</x-filament-panels::page>