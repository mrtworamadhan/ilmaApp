<!DOCTYPE html>
<html>
<head>
    <style>
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
        .percent {
            text-align: right;
        }
        tfoot .total {
            font-weight: bold;
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="header">LAPORAN REALISASI ANGGARAN</div>
    <div class="subheader">Tahun Ajaran: {{ $academicYear ?? 'N/A' }}</div>
    <div class="subheader">Departemen: {{ $departmentName ?? 'Semua Departemen' }}</div>

    <table>
        <thead>
            <tr>
                <th>Pos Anggaran (COA)</th>
                <th>Deskripsi</th>
                <th class="numeric">Dianggarkan (Rp)</th>
                <th class="numeric">Realisasi (Rp)</th>
                <th class="percent">Realisasi (%)</th>
                <th class="numeric">Sisa (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalDianggarkan = 0;
                $totalRealisasi = 0;
            @endphp

            @forelse($items as $item)
                @php
                    // Logika dari kolom tabel Filament
                    $dianggarkan = $item->planned_amount;
                    $realisasi = $item->expenses_sum_amount ?? 0;
                    $sisa = $dianggarkan - $realisasi;
                    $persentase = ($dianggarkan > 0) ? ($realisasi / $dianggarkan) * 100 : 0;
                    
                    // Akumulasi total
                    $totalDianggarkan += $dianggarkan;
                    $totalRealisasi += $realisasi;
                @endphp
                <tr>
                    <td>
                        {{ $item->account->name ?? 'N/A' }}
                        <br>
                        <small>({{ $item->account->code ?? 'N/A' }})</small>
                    </td>
                    <td>{{ $item->description }}</td>
                    <td class="numeric">{{ number_format($dianggarkan, 2, ',', '.') }}</td>
                    <td class="numeric">{{ number_format($realisasi, 2, ',', '.') }}</td>
                    <td class="percent">{{ number_format($persentase, 2, ',', '.') }} %</td>
                    <td class="numeric">{{ number_format($sisa, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">Tidak ada data untuk filter yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
        
        {{-- Baris Total di Footer --}}
        @if($items->isNotEmpty())
            <tfoot>
                <tr class="total">
                    @php
                        $totalSisa = $totalDianggarkan - $totalRealisasi;
                        $totalPersentase = ($totalDianggarkan > 0) ? ($totalRealisasi / $totalDianggarkan) * 100 : 0;
                    @endphp
                    <td colspan="2" style="text-align: right; font-weight: bold;">TOTAL KESELURUHAN</td>
                    <td class="numeric">{{ number_format($totalDianggarkan, 2, ',', '.') }}</td>
                    <td class="numeric">{{ number_format($totalRealisasi, 2, ',', '.') }}</td>
                    <td class="percent">{{ number_format($totalPersentase, 2, ',', '.') }} %</td>
                    <td class="numeric">{{ number_format($totalSisa, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

</body>
</html>