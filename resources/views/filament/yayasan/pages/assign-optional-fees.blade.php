<x-filament-panels::page>

        {{-- 1. Render Form Filter --}}
            <x-filament::section heading="Filter Siswa dan Biaya">
                {{ $this->form }}
            </x-filament::section>

        {{-- 2. Render Tabel Siswa (jika filter sudah diisi) --}}
        @if($this->data['school_id'] && $this->data['fee_structure_id'])
                <x-filament::section heading="Pilih Siswa (Assign Massal)">
                    {{ $this->table }}
                </x-filament::section>
        @else
            <x-filament::section>
                <div class="p-4 text-center text-gray-500">
                    <p>Silakan pilih Sekolah dan Biaya Opsional terlebih dahulu untuk menampilkan daftar siswa.</p>
                </div>
            </x-filament::section>
        @endif

</x-filament-panels::page>