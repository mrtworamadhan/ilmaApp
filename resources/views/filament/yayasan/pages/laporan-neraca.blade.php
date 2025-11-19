<x-filament-panels::page>

    {{-- ✅ FORM FILTER --}}
    <x-filament::section icon="heroicon-o-calendar-days" heading="Pilih Periode Laporan">
        <form wire:submit="applyFilters" class="space-y-4">
            {{ $this->filterForm }}
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Laporan Posisi Keuangan (Neraca) akan menampilkan posisi saldo <b>per Tanggal Selesai</b>.<br>
                Penghasilan Komprehensif (Laba/Rugi) Berjalan akan dihitung dari <b>Tanggal Mulai</b> s/d <b>Tanggal Selesai</b>.
            </p>
            <x-filament::button type="submit" icon="heroicon-o-funnel">
                Terapkan Filter
            </x-filament::button>
        </form>
    </x-filament::section>

    {{-- ✅ BAGIAN 2: LAPORAN NERACA (POSISI KEUANGAN) --}}
    <x-filament::section icon="heroicon-o-scale" heading="Laporan Posisi Keuangan (Neraca)">
        <div class="cf-stack">
            
            {{-- =============================================== --}}
            {{-- BAGIAN ASET (REFACTOR 1) --}}
            {{-- =============================================== --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">ASET</h3>
                @forelse($this->hasilAktiva as $akun)
                    @if($akun->balance != 0)
                        <div class="cf-row">
                            <span class="text-gray-600 dark:text-gray-400 pl-4">{{ $akun->name }}</span>
                            <span class="cf-amount {{ $akun->balance >= 0 ? 'cf-pos' : 'cf-neg' }}">
                                Rp {{ number_format($akun->balance, 2, ',', '.') }}
                            </span>
                        </div>
                    @endif
                @empty
                    <p class="text-gray-500 pl-4">Tidak ada data Aset.</p>
                @endforelse

                {{-- Total Aset --}}
                <div class="cf-row cf-total">
                    <span class="font-semibold">TOTAL ASET</span>
                    <span class="cf-amount cf-pos">
                        Rp {{ number_format($this->totalAktiva, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            {{-- =============================================== --}}
            {{-- BAGIAN LIABILITAS & ASET NETO (REFACTOR 2) --}}
            {{-- =============================================== --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">LIABILITAS DAN ASET NETO</h3>
                
                {{-- LIABILITAS --}}
                @forelse($this->hasilKewajiban as $akun)
                    @if($akun->balance != 0)
                        <div class="cf-row">
                            <span class="text-gray-600 dark:text-gray-400 pl-4">{{ $akun->name }}</span>
                            <span class="cf-amount {{ $akun->balance >= 0 ? 'cf-pos' : 'cf-neg' }}">
                                Rp {{ number_format($akun->balance, 2, ',', '.') }}
                            </span>
                        </div>
                    @endif
                @empty
                    <p class="text-gray-500 pl-4">Tidak ada data Liabilitas.</p>
                @endforelse

                {{-- Total Liabilitas --}}
                <div class="cf-row cf-subtotal">
                    <span class="font-semibold pl-2">Total Liabilitas</span>
                    <span class="cf-amount cf-pos">
                        Rp {{ number_format($this->totalKewajiban, 2, ',', '.') }}
                    </span>
                </div>

                {{-- ASET NETO --}}
                @forelse($this->hasilEkuitas as $akun)
                    @if($akun->balance != 0)
                        <div class="cf-row">
                            <span class="text-gray-600 dark:text-gray-400 pl-4">{{ $akun->name }}</span>
                            <span class="cf-amount {{ $akun->balance >= 0 ? 'cf-pos' : 'cf-neg' }}">
                                Rp {{ number_format($akun->balance, 2, ',', '.') }}
                            </span>
                        </div>
                    @endif
                @empty
                    <p class="text-gray-500 pl-4">Tidak ada data Aset Neto.</p>
                @endforelse
                
                {{-- Laba/Rugi --}}
                <div class="cf-row">
                    <span class="text-gray-600 dark:text-gray-400 pl-4">Penghasilan Komprehensif Periode Berjalan</span>
                    <span class="cf-amount {{ $this->labaRugiPeriodeIni >= 0 ? 'cf-pos' : 'cf-neg' }}">
                        Rp {{ number_format($this->labaRugiPeriodeIni, 2, ',', '.') }}
                    </span>
                </div>
                <div class="cf-row">
                    <span class="text-gray-600 dark:text-gray-400 pl-4">Saldo Awal Aset Neto (Laba Ditangguhkan)</span>
                    <span class="cf-amount {{ $this->labaDitangguhkan >= 0 ? 'cf-pos' : 'cf-neg' }}">
                        Rp {{ number_format($this->labaDitangguhkan, 2, ',', '.') }}
                    </span>
                </div>

                {{-- Total Aset Neto --}}
                <div class="cf-row cf-subtotal">
                    <span class="font-semibold pl-2">Total Aset Neto</span>
                    <span class="cf-amount cf-pos">
                        Rp {{ number_format($this->totalEkuitas, 2, ',', '.') }}
                    </span>
                </div>

                {{-- Total Liabilitas & Aset Neto --}}
                <div class="cf-row cf-total">
                    <span class="font-semibold">TOTAL LIABILITAS DAN ASET NETO</span>
                    <span class="cf-amount cf-pos">
                        Rp {{ number_format($this->totalKewajibanDanEkuitas, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            {{-- 6. INDIKATOR BALANCE (Sudah benar) --}}
            <div class="mt-4">
                @if(round($this->totalAktiva) == round($this->totalKewajibanDanEkuitas) && $this->totalAktiva != 0)
                    <p class="font-semibold text-lg text-green-600 dark:text-green-400">✅ BALANCE</p>
                @else
                    <p class="font-semibold text-lg text-red-600 dark:text-red-400">❌ TIDAK BALANCE</p>
                @endif
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    - Total Aset: Rp {{ number_format($this->totalAktiva, 2, ',', '.') }}<br>
                    - Total Liabilitas & Aset Neto: Rp {{ number_format($this->totalKewajibanDanEkuitas, 2, ',', '.') }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Laba/Rugi Periode Berjalan: Rp {{ number_format($this->labaRugiPeriodeIni, 2, ',', '.') }}<br>
                    Laba Ditangguhkan: Rp {{ number_format($this->labaDitangguhkan, 2, ',', '.') }}
                </p>
            </div>
        </div>
    </x-filament::section>


    {{-- ✅ STYLE KUSTOM DARI FILE-MU (Tidak diubah) --}}
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
            .fi-body .cf-total {
                border-top: 4px double rgb(156 163 175) !important;
                margin-top: 1rem !important;
                padding-top: 1rem !important;
            }
        </style>
    @endpush

</x-filament-panels::page>