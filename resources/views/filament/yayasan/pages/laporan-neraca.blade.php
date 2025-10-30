<x-filament-panels::page>

    {{-- ✅ FORM FILTER --}}
    <x-filament::section icon="heroicon-o-calendar-days" heading="Pilih Periode Laporan">
        <form wire:submit="applyFilters" class="space-y-4">
            {{ $this->filterForm }}
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Laporan Neraca akan menampilkan posisi saldo <b>per Tanggal Selesai</b>.<br>
                Laba/Rugi Berjalan akan dihitung dari <b>Tanggal Mulai</b> s/d <b>Tanggal Selesai</b>.
            </p>
            <x-filament::button type="submit" icon="heroicon-o-funnel">
                Terapkan Filter
            </x-filament::button>
        </form>
    </x-filament::section>

    {{-- ✅ BAGIAN 2: LAPORAN NERACA --}}
    <x-filament::section icon="heroicon-o-scale" heading="Laporan Neraca (Posisi Keuangan)">
        <div class="cf-stack">
            
            {{-- BAGIAN AKTIVA --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">AKTIVA</h3>
                @forelse($hasilAktiva as $akun)
                    @if($akun->balance != 0)
                        <div class="cf-row">
                            <span class="text-gray-600 dark:text-gray-400 pl-4">{{ $akun->name }}</span>
                            <span class="cf-amount">
                                Rp {{ number_format($akun->balance, 2, ',', '.') }}
                            </span>
                        </div>
                    @endif
                @empty
                    <p class="text-gray-500 pl-4">Tidak ada data Aktiva.</p>
                @endforelse

                {{-- TOTAL AKTIVA --}}
                <div class="cf-row cf-subtotal font-bold text-base">
                    <span class="text-gray-700 dark:text-gray-300">TOTAL AKTIVA</span>
                    <span class="cf-amount">
                        Rp {{ number_format($totalAktiva, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            {{-- BAGIAN KEWAJIBAN & EKUITAS --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">KEWAJIBAN & EKUITAS</h3>
                
                {{-- KEWAJIBAN --}}
                <h4 class="font-medium text-gray-700 dark:text-gray-300 mt-2">Kewajiban</h4>
                @forelse($hasilKewajiban as $akun)
                     @if($akun->balance != 0)
                        <div class="cf-row">
                            <span class="text-gray-600 dark:text-gray-400 pl-4">{{ $akun->name }}</span>
                            <span class="cf-amount">
                                Rp {{ number_format($akun->balance, 2, ',', '.') }}
                            </span>
                        </div>
                    @endif
                @empty
                    <div class="cf-row"><span class="text-gray-500 pl-4">Tidak ada data Kewajiban.</span></div>
                @endforelse
                <div class="cf-row font-semibold border-t dark:border-gray-600">
                    <span class="text-gray-700 dark:text-gray-300 pl-4">Total Kewajiban</span>
                    <span class="cf-amount">
                        Rp {{ number_format($totalKewajiban, 2, ',', '.') }}
                    </span>
                </div>

                {{-- EKUITAS --}}
                <h4 class="font-medium text-gray-700 dark:text-gray-300 mt-4">Ekuitas</h4>
                @forelse($hasilEkuitas as $akun)
                     @if($akun->balance != 0)
                        <div class="cf-row">
                            <span class="text-gray-600 dark:text-gray-400 pl-4">{{ $akun->name }}</span>
                            <span class="cf-amount">
                                Rp {{ number_format($akun->balance, 2, ',', '.') }}
                            </span>
                        </div>
                    @endif
                @empty
                    <div class="cf-row"><span class="text-gray-500 pl-4">Tidak ada Modal Awal.</span></div>
                @endforelse
                
                {{-- LABA RUGI PERIODE BERJALAN --}}
                <div class="cf-row">
                    <span class="text-gray-600 dark:text-gray-400 pl-4">Laba (Rugi) Periode Berjalan</span>
                    <span class="cf-amount {{ $labaRugiPeriodeIni >= 0 ? 'cf-pos' : 'cf-neg' }}">
                        @if($labaRugiPeriodeIni < 0)
                            (Rp {{ number_format(abs($labaRugiPeriodeIni), 2, ',', '.') }})
                        @else
                            Rp {{ number_format($labaRugiPeriodeIni, 2, ',', '.') }}
                        @endif
                    </span>
                </div>
                
                <div class="cf-row font-semibold border-t dark:border-gray-600">
                    <span class="text-gray-700 dark:text-gray-300 pl-4">Total Ekuitas</span>
                    <span class="cf-amount">
                        Rp {{ number_format($totalEkuitas + $labaRugiPeriodeIni, 2, ',', '.') }}
                    </span>
                </div>

                {{-- TOTAL KEWAJIBAN + EKUITAS --}}
                <div class="cf-row cf-subtotal font-bold text-base">
                    <span class="text-gray-700 dark:text-gray-300">TOTAL KEWAJIBAN & EKUITAS</span>
                    <span class="cf-amount">
                        Rp {{ number_format($totalKewajiban + $totalEkuitas + $labaRugiPeriodeIni, 2, ',', '.') }}
                    </span>
                </div>

            </div>

        </div>
    </x-filament::section>
    
    {{-- ✅ BAGIAN 3: CEK KESEIMBANGAN --}}
    @php
        $totalLiabilitiesAndEquity = $totalKewajiban + $totalEkuitas + $labaRugiPeriodeIni;
        $isBalanced = abs($totalAktiva - $totalLiabilitiesAndEquity) < 0.01; // Toleransi pembulatan
    @endphp

    <x-filament::card>
        <div class="cf-row">
            <h3 class="text-lg font-semibold {{ $isBalanced ? 'text-success-600' : 'text-danger-600' }}">
                @if($isBalanced)
                    <x-filament::icon icon="heroicon-o-check-circle" class="w-6 h-6 inline-block" />
                    BALANCE (SEIMBANG)
                @else
                    <x-filament::icon icon="heroicon-o-x-circle" class="w-6 h-6 inline-block" />
                    TIDAK SEIMBANG (UNBALANCED)
                @endif
            </h3>
            <p class="cf-amount text-xl font-bold {{ $isBalanced ? 'text-success-600' : 'text-danger-600' }}">
                Selisih: Rp {{ number_format(abs($totalAktiva - $totalLiabilitiesAndEquity), 2, ',', '.') }}
            </p>
        </div>
    </x-filament::card>


    {{-- ✅ STYLE KUSTOM DARI FILE-MU --}}
    @push('styles')
        <style>
            .fi-body .cf-stack {
                display: grid;
                row-gap: 2rem !important;
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
            .fi-body .cf-pos { color: #16a34a !important; }
            .fi-body .cf-neg { color: #dc2626 !important; }
            .fi-body .cf-subtotal {
                border-top: 2px solid rgb(156 163 175) !important;
                margin-top: 1rem !important;
                padding-top: 1rem !important;
            }
            .dark .fi-body .cf-subtotal { border-top-color: rgb(75 85 99) !important; }
        </style>
    @endpush
</x-filament-panels::page>