<x-filament-panels::page>

    {{-- 1. Render Form Filter --}}
    <x-filament::section icon="heroicon-o-funnel" heading="Filter Laporan">
        {{-- Tombol submit tidak perlu lagi karena form 'live' --}}
        {{ $this->form }}
        <x-filament::button type="submit" icon="heroicon-o-funnel">
            Terapkan Filter
        </x-filament::button>
    </x-filament::section>

    {{-- 2. Render Tabel Hasil (Total sudah otomatis di footer) --}}
    <x-filament::section class="mt-6">
        {{ $this->table }}
    </x-filament::section>

    {{-- 3. HAPUS @push('styles') karena sudah tidak perlu --}}

</x-filament-panels::page>