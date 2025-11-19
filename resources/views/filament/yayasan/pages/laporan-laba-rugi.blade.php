<x-filament-panels::page>

    {{-- ✅ FORM FILTER (Tidak Berubah) --}}
    <x-filament::section icon="heroicon-o-calendar-days" heading="Pilih Rentang Tanggal">
        <form wire:submit="applyFilters" class="space-y-4">
            {{ $this->filterForm }}
            <x-filament::button type="submit" icon="heroicon-o-funnel">
                Terapkan Filter
            </x-filament::button>
        </form>
    </x-filament::section>

    {{-- ✅ LAPORAN PENGHASILAN KOMPREHENSIF (2 Kolom) --}}
    <x-filament::section icon="heroicon-o-chart-bar-square" heading="Laporan Aktivitas (ISAK 35)">
        <div class="cf-stack">
            
            {{-- HEADER TABEL (Baru) --}}
            <div class="cf-row cf-header">
                <span>Keterangan</span>
                <span class="cf-amount">Tanpa Pembatasan</span>
                <span class="cf-amount">Dengan Pembatasan</span>
                <span class="cf-amount">Total</span>
            </div>

            {{-- =============================================== --}}
            {{-- 1. PENDAPATAN --}}
            {{-- =============================================== --}}
            <div class="cf-row-group-header">
                <span>Pendapatan & Sumbangan</span>
            </div>

            {{-- Pendapatan Tidak Terikat (SPP, dll) --}}
            @foreach($hasilPendapatanTidakTerikat as $akun)
                @if($akun->total > 0)
                <div class="cf-row">
                    <span class="pl-6">{{ $akun->name }}</span>
                    <span class="cf-amount">{{ number_format($akun->total, 2, ',', '.') }}</span>
                    <span class="cf-amount">-</span>
                    <span class="cf-amount">{{ number_format($akun->total, 2, ',', '.') }}</span>
                </div>
                @endif
            @endforeach
            
            {{-- Pendapatan Terikat (BOS, dll) --}}
            @foreach($hasilPendapatanTerikat as $akun)
                @if($akun->total > 0)
                <div class="cf-row">
                    <span class="pl-6">{{ $akun->name }}</span>
                    <span class="cf-amount">-</span>
                    <span class="cf-amount">{{ number_format($akun->total, 2, ',', '.') }}</span>
                    <span class="cf-amount">{{ number_format($akun->total, 2, ',', '.') }}</span>
                </div>
                @endif
            @endforeach
            
            {{-- Baris Pelepasan Dana (Jurnal #2) --}}
            <div class="cf-row">
                <span class="pl-6 italic">Dana Dilepaskan dari Pembatasan</span>
                <span class="cf-amount italic cf-pos">{{ number_format($totalPelepasanDana_Kredit, 2, ',', '.') }}</span>
                <span class="cf-amount italic cf-neg">({{ number_format($totalPelepasanDana_Debit, 2, ',', '.') }})</span>
                <span class="cf-amount italic font-semibold">-</span>
            </div>

            {{-- Total Pendapatan & Aktivitas --}}
            <div class="cf-row cf-subtotal">
                <span class="font-semibold">Total Pendapatan & Pelepasan</span>
                <span class="cf-amount font-semibold">{{ number_format($totalPendapatanTidakTerikat + $totalPelepasanDana_Kredit, 2, ',', '.') }}</span>
                <span class="cf-amount font-semibold">{{ number_format($totalPendapatanTerikat - $totalPelepasanDana_Debit, 2, ',', '.') }}</span>
                <span class="cf-amount font-semibold">{{ number_format($totalPendapatan, 2, ',', '.') }}</span>
            </div>

            {{-- =============================================== --}}
            {{-- 2. BEBAN --}}
            {{-- =============================================== --}}
            <div class="cf-row-group-header">
                <span>Beban</span>
            </div>
            
            @forelse($hasilBeban as $akun)
                @if($akun->total > 0)
                <div class="cf-row">
                    <span class="pl-6">{{ $akun->name }}</span>
                    <span class="cf-amount cf-neg">({{ number_format($akun->total, 2, ',', '.') }})</span>
                    <span class="cf-amount">-</span>
                    <span class="cf-amount cf-neg">({{ number_format($akun->total, 2, ',', '.') }})</span>
                </div>
                @endif
            @empty
                <div class="cf-row">
                    <span class="pl-6 text-gray-500">Tidak ada data beban.</span>
                    <span class="cf-amount">-</span>
                    <span class="cf-amount">-</span>
                    <span class="cf-amount">-</span>
                </div>
            @endforelse

            {{-- Total Beban --}}
            <div class="cf-row cf-subtotal">
                <span class="font-semibold">Total Beban</span>
                <span class="cf-amount font-semibold cf-neg">({{ number_format($totalBeban, 2, ',', '.') }})</span>
                <span class="cf-amount font-semibold">-</span>
                <span class="cf-amount font-semibold cf-neg">({{ number_format($totalBeban, 2, ',', '.') }})</span>
            </div>

            {{-- =============================================== --}}
            {{-- 3. TOTAL AKHIR --}}
            {{-- =============================================== --}}
            <div class="cf-row cf-total">
                <span class="text-lg font-bold">Total Kenaikan/(Penurunan) Aset Neto</span>
                <span class="text-lg font-bold cf-amount {{ $totalSurplusDefisit_TidakTerikat >= 0 ? 'cf-pos' : 'cf-neg' }}">
                    {{ number_format($totalSurplusDefisit_TidakTerikat, 2, ',', '.') }}
                </span>
                <span class="text-lg font-bold cf-amount {{ $totalSurplusDefisit_Terikat >= 0 ? 'cf-pos' : 'cf-neg' }}">
                    {{ number_format($totalSurplusDefisit_Terikat, 2, ',', '.') }}
                </span>
                <span class="text-lg font-bold cf-amount {{ $totalPerubahanAsetNeto >= 0 ? 'cf-pos' : 'cf-neg' }}">
                    {{ number_format($totalPerubahanAsetNeto, 2, ',', '.') }}
                </span>
            </div>
            
        </div>
    </x-filament::section>


    {{-- ✅ STYLE KUSTOM (Diadaptasi dari file V1-mu untuk 4 KOLOM) --}}
    @push('styles')
        <style>
            .fi-body .cf-stack {
                display: grid;
                row-gap: 1rem !important;
            }

            .fi-body .cf-row {
                display: grid !important;
                /* --- INI PERBAIKANNYA: 4 KOLOM --- */
                grid-template-columns: 2fr 1.2fr 1.2fr 1.2fr !important;
                align-items: center !important;
                padding-top: .5rem !important;
                padding-bottom: .5rem !important;
                font-size: 0.9rem !important;
            }

            /* Style untuk header (Keterangan, Tanpa Pembatasan, dll) */
            .fi-body .cf-header {
                font-size: 0.8rem !important;
                font-weight: 600;
                color: rgb(107 114 128);
                border-bottom: 1px solid rgb(229 231 235);
            }
            .dark .fi-body .cf-header {
                color: rgb(156 163 175);
                border-bottom-color: rgb(55 65 81);
            }
            
            /* Style untuk header grup (Pendapatan, Beban) */
            .fi-body .cf-row-group-header {
                font-size: 1rem !important;
                font-weight: 600;
                margin-top: 0.5rem;
                padding-bottom: 0.25rem;
                border-bottom: 1px solid rgb(229 231 235);
            }
            .dark .fi-body .cf-row-group-header {
                border-bottom-color: rgb(55 65 81);
            }

            .fi-body .cf-amount {
                text-align: right !important;
                font-variant-numeric: tabular-nums !important;
                font-size: 0.9rem !important;
                font-weight: 500;
            }

            .fi-body .cf-pos { color: #16a34a !important; }
            .fi-body .cf-neg { color: #dc2626 !important; }
            .dark .fi-body .cf-pos { color: #22c55e !important; }
            .dark .fi-body .cf-neg { color: #ef4444 !important; }

            .fi-body .cf-subtotal {
                border-top: 1px dashed rgb(209 213 219) !important;
                margin-top: .25rem !important;
                padding-top: .75rem !important;
            }
            .dark .fi-body .cf-subtotal {
                border-top-color: rgb(55 65 81) !important;
            }

            .fi-body .cf-total {
                border-top: 4px double rgb(156 163 175) !important;
                margin-top: 1.5rem !important;
                padding-top: 1.5rem !important;
            }
            .dark .fi-body .cf-total {
                border-top-color: rgb(75 85 99) !important;
            }
        </style>
    @endpush

</x-filament-panels::page>