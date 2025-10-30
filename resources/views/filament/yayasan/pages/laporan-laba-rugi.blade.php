<x-filament-panels::page>

    {{-- ✅ FORM FILTER (Mengadopsi style dari file-mu) --}}
    <x-filament::section icon="heroicon-o-calendar-days" heading="Pilih Rentang Tanggal">
        <form wire:submit="applyFilters" class="space-y-4">
            {{ $this->filterForm }}

            <x-filament::button type="submit" icon="heroicon-o-funnel">
                Terapkan Filter
            </x-filament::button>
        </form>
    </x-filament::section>

    {{-- ✅ BAGIAN 2: LAPORAN KONSOLIDASI (Mengadopsi style dari file-mu) --}}
    <x-filament::section icon="heroicon-o-chart-bar-square" heading="Laporan Laba Rugi Konsolidasi">
        <div class="cf-stack">
            
            {{-- BAGIAN PENDAPATAN --}}
            @forelse($hasilPendapatan as $akun)
                @if($akun->total > 0) {{-- Hanya tampilkan jika ada saldo --}}
                    <div class="cf-row">
                        <span class="text-gray-600 dark:text-gray-400">{{ $akun->name }}</span>
                        <span class="cf-amount cf-pos"> {{-- Style 'cf-pos' --}}
                            Rp {{ number_format($akun->total, 2, ',', '.') }}
                        </span>
                    </div>
                @endif
            @empty
                <div class="cf-row">
                    <span class="text-gray-500">Tidak ada data pendapatan.</span>
                    <span></span>
                </div>
            @endforelse

            {{-- TOTAL PENDAPATAN (SUBTOTAL) --}}
            <div class="cf-row cf-subtotal font-semibold"> {{-- Style 'cf-subtotal' --}}
                <span class="text-gray-700 dark:text-gray-300">Total Pendapatan</span>
                <span class="cf-amount cf-pos">
                    Rp {{ number_format($totalPendapatan, 2, ',', '.') }}
                </span>
            </div>

            {{-- BAGIAN BEBAN --}}
            @forelse($hasilBeban as $akun)
                 @if($akun->total > 0) {{-- Hanya tampilkan jika ada saldo --}}
                    <div class="cf-row">
                        <span class="text-gray-600 dark:text-gray-400">{{ $akun->name }}</span>
                        <span class="cf-amount cf-neg"> {{-- Style 'cf-neg' --}}
                            (Rp {{ number_format($akun->total, 2, ',', '.') }})
                        </span>
                    </div>
                @endif
            @empty
                <div class="cf-row">
                    <span class="text-gray-500">Tidak ada data beban.</span>
                    <span></span>
                </div>
            @endforelse

            {{-- TOTAL BEBAN (SUBTOTAL) --}}
            <div class="cf-row cf-subtotal font-semibold">
                <span class="text-gray-700 dark:text-gray-300">Total Beban Operasional</span>
                <span class="cf-amount cf-neg">
                    (Rp {{ number_format($totalBeban, 2, ',', '.') }})
                </span>
            </div>

        </div>
    </x-filament::section>

    {{-- ✅ BAGIAN 3: KESIMPULAN (Mengadopsi style dari file-mu) --}}
    <x-filament::card>
        <div class="cf-row">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Laba Bersih Konsolidasi</h3>
            <p class="cf-amount text-2xl font-bold {{ $labaRugi >= 0 ? 'cf-pos' : 'cf-neg' }}">
                @if($labaRugi < 0)
                    (Rp {{ number_format(abs($labaRugi), 2, ',', '.') }})
                @else
                    Rp {{ number_format($labaRugi, 2, ',', '.') }}
                @endif
            </p>
        </div>
    </x-filament::card>


    {{-- ✅ STYLE KUSTOM DARI FILE-MU --}}
    @push('styles')
        <style>
            .fi-body .cf-stack {
                display: grid;
                row-gap: 1.5rem !important;
            }

            .fi-body .cf-stack .fi-card {
                padding: 1.5rem !important;
                border-radius: .75rem !important;
            }

            .fi-body .cf-stack .fi-section {
                border-radius: .75rem !important;
            }

            .fi-body .cf-row {
                display: grid !important;
                grid-template-columns: 1fr 16rem !important;
                align-items: center !important;
                padding-top: .5rem !important;
                padding-bottom: .5rem !important;
                font-size: 0.9rem !important;
            }

            .fi-body .cf-amount {
                text-align: right !important;
                font-variant-numeric: tabular-nums !important;
                font-size: 1rem !important;
                font-weight: 500;
            }

            .fi-body .cf-pos {
                color: #16a34a !important;
            }

            .fi-body .cf-neg {
                color: #dc2626 !important;
            }

            .fi-body .cf-subtotal {
                border-top: 1px dashed rgb(209 213 219) !important;
                margin-top: .75rem !important;
                padding-top: .75rem !important;
            }

            .dark .fi-body .cf-subtotal {
                border-top-color: rgb(55 65 81) !important;
            }
        </style>
    @endpush
</x-filament-panels::page>