<!DOCTYPE html>
<html>
<head>
    <style>
        /* Style sederhana agar rapi di PDF & Excel */
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #999;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .header {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }
        .subheader {
            font-size: 12px;
            text-align: center;
            margin-bottom: 15px;
        }
        .numeric {
            text-align: right;
        }
        /* Baris Kategori (Pendapatan, Beban) */
        .category-header {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        /* Baris Total Kategori */
        .total-row {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        /* Baris Grand Total (Final) */
        .grand-total-header {
            font-weight: bold;
            font-size: 11px;
            background-color: #e0e0e0;
        }
        .grand-total-value {
            font-weight: bold;
            font-size: 11px;
            background-color: #e0e0e0;
        }
        .item-indent {
            padding-left: 15px;
        }
    </style>
</head>
<body>
    @php
        // Format tanggal untuk judul
        $start = \Carbon\Carbon::parse($startDate)->isoFormat('D MMMM Y');
        $end = \Carbon\Carbon::parse($endDate)->isoFormat('D MMMM Y');
    @endphp

    <div class="header">LAPORAN PENGHASILAN KOMPREHENSIF</div>
    <div class="subheader">{{ $schoolName ?? 'Semua Sekolah (Yayasan)' }}</div>
    <div class="subheader">Periode: {{ $start }} s/d {{ $end }}</div>

    <table>
        <thead>
            <tr>
                <th>Keterangan</th>
                <th class="numeric">Tanpa Pembatasan</th>
                <th class="numeric">Dengan Pembatasan</th>
                <th class="numeric">Total</th>
            </tr>
        </thead>
        <tbody>
            
            {{-- ====================================================== --}}
            {{-- 1. PENDAPATAN --}}
            {{-- ====================================================== --}}
            <tr class="category-header">
                <td colspan="4">PENDAPATAN</td>
            </tr>
            
            {{-- Pendapatan Tanpa Pembatasan --}}
            @forelse($hasilPendapatanTidakTerikat as $akun)
                @if($akun->total > 0)
                <tr>
                    <td class="item-indent">{{ $akun->name }}</td>
                    <td class="numeric">{{ number_format($akun->total, 2, ',', '.') }}</td>
                    <td class="numeric">0,00</td>
                    <td class="numeric">{{ number_format($akun->total, 2, ',', '.') }}</td>
                </tr>
                @endif
            @empty
                {{-- Kosong --}}
            @endforelse

            {{-- Pendapatan Dengan Pembatasan --}}
            @forelse($hasilPendapatanTerikat as $akun)
                 @if($akun->total > 0)
                 <tr>
                    <td class="item-indent">{{ $akun->name }}</td>
                    <td class="numeric">0,00</td>
                    <td class="numeric">{{ number_format($akun->total, 2, ',', '.') }}</td>
                    <td class="numeric">{{ number_format($akun->total, 2, ',', '.') }}</td>
                </tr>
                @endif
            @empty
                {{-- Kosong --}}
            @endforelse

            <tr class="total-row">
                <td>Total Pendapatan</td>
                <td class="numeric">{{ number_format($totalPendapatanTidakTerikat, 2, ',', '.') }}</td>
                <td class="numeric">{{ number_format($totalPendapatanTerikat, 2, ',', '.') }}</td>
                <td class="numeric">{{ number_format($totalPendapatan, 2, ',', '.') }}</td>
            </tr>

            {{-- ====================================================== --}}
            {{-- 2. PELEPASAN DANA (JURNAL #2) --}}
            {{-- ====================================================== --}}
            <tr class="category-header">
                <td colspan="4">PELEPASAN PEMBATASAN ASET NETO</td>
            </tr>
            <tr>
                <td class="item-indent">Pelepasan Dana Terikat (Beban BOS, dll)</td>
                {{-- Masuk (Kredit) ke Tanpa Pembatasan --}}
                <td class="numeric">{{ number_format($totalPelepasanDana_Kredit, 2, ',', '.') }}</td>
                {{-- Keluar (Debit) dari Dengan Pembatasan --}}
                <td class="numeric">{{ number_format(-$totalPelepasanDana_Debit, 2, ',', '.') }}</td> 
                {{-- Total harusnya 0 --}}
                <td class="numeric">{{ number_format($totalPelepasanDana_Kredit - $totalPelepasanDana_Debit, 2, ',', '.') }}</td>
            </tr>

            {{-- ====================================================== --}}
            {{-- 3. BEBAN --}}
            {{-- ====================================================== --}}
             <tr class="category-header">
                <td colspan="4">BEBAN</td>
            </tr>
            
            {{-- Semua Beban masuk ke kolom "Tanpa Pembatasan" --}}
            @forelse($hasilBeban as $akun)
                @if($akun->total > 0)
                <tr>
                    <td class="item-indent">{{ $akun->name }}</td>
                    <td class="numeric">{{ number_format($akun->total, 2, ',', '.') }}</td>
                    <td class="numeric">0,00</td>
                    <td class="numeric">{{ number_format($akun->total, 2, ',', '.') }}</td>
                </tr>
                @endif
            @empty
                 <tr>
                    <td class="item-indent" colspan="4">Tidak ada beban.</td>
                </tr>
            @endforelse

            <tr class="total-row">
                <td>Total Beban</td>
                <td class="numeric">{{ number_format($totalBeban, 2, ',', '.') }}</td>
                <td class="numeric">0,00</td>
                <td class="numeric">{{ number_format($totalBebanGabungan, 2, ',', '.') }}</td>
            </tr>

            {{-- ====================================================== --}}
            {{-- 4. GRAND TOTAL (SURPLUS / DEFISIT) --}}
            {{-- ====================================================== --}}
            <tr>
                <td colspan="4">&nbsp;</td> {{-- Baris kosong sebagai spasi --}}
            </tr>

            <tr class="grand-total-header">
                <td>PERUBAHAN ASET NETO (SURPLUS/DEFISIT)</td>
                <td class="numeric grand-total-value">{{ number_format($totalSurplusDefisit_TidakTerikat, 2, ',', '.') }}</td>
                <td class="numeric grand-total-value">{{ number_format($totalSurplusDefisit_Terikat, 2, ',', '.') }}</td>
                <td class="numeric grand-total-value">{{ number_format($totalPerubahanAsetNeto, 2, ',', '.') }}</td>
            </tr>

        </tbody>
    </table>

</body>
</html>