<x-filament-panels::page>

    {{-- 1. Render Form Filter --}}
    <form wire:submit="applyFilters">
        {{ $this->filterForm }}

        <x-filament::button type="submit" class="mt-4">
            Terapkan Filter
        </x-filament::button>
    </form>

    {{-- 2. Render Tabel Hasil --}}
    <div class="mt-6">
        {{ $this->table }}
    </div>

</x-filament-panels::page>