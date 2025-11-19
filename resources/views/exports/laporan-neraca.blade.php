<!DOCTYPE html>
<html>
<head>
    <style>
        /* Style sederhana agar rapi di PDF */
        body {
            font-family: sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
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
        }
        .total {
            font-weight: bold;
        }
        /* Penyesuaian untuk nilai numerik */
        .numeric {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">LAPORAN NERACA</div>
    <div class="subheader">{{ $namaSekolah ?? 'Semua Sekolah (Level Yayasan)' }}</div>
    <div class="subheader">Periode: {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMMM Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMMM Y') }}</div>
    <br>

    {{-- Tabel ASET --}}
    <table>
        <thead>
            <tr>
                <th colspan="2">ASET</th>
            </tr>
        </thead>
        <tbody>
            @forelse($hasilAktiva as $akun)
                <tr>
                    <td>{{ $akun->name }}</td>
                    <td class="numeric">{{ number_format($akun->balance, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Tidak ada data Aset.</td>
                </tr>
            @endforelse
            <tr class="total">
                <td>TOTAL ASET</td>
                <td class="numeric">{{ number_format($totalAktiva, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <br>

    {{-- Tabel LIABILITAS & ASET NETO --}}
    <table>
        <thead>
            <tr>
                <th colspan="2">LIABILITAS DAN ASET NETO</th>
            </tr>
        </thead>
        <tbody>
            {{-- Bagian Liabilitas --}}
            <tr>
                <th colspan="2" style="background-color: #f9f9f9;">Liabilitas</th>
            </tr>
            @forelse($hasilKewajiban as $akun)
                <tr>
                    <td>{{ $akun->name }}</td>
                    <td class="numeric">{{ number_format($akun->balance, 2, ',', '.') }}</td>
                </tr>
            @empty
                 <tr>
                    <td colspan="2">Tidak ada data Liabilitas.</td>
                </tr>
            @endforelse
            <tr class="total">
                <td>Total Liabilitas</td>
                <td class="numeric">{{ number_format($totalKewajiban, 2, ',', '.') }}</td>
            </tr>

            {{-- Bagian Aset Neto (Ekuitas) --}}
            <tr>
                <th colspan="2" style="background-color: #f9f9f9;">Aset Neto</th>
            </tr>
            @forelse($hasilEkuitas as $akun)
                 <tr>
                    <td>{{ $akun->name }}</td>
                    <td class="numeric">{{ number_format($akun->balance, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Tidak ada data Aset Neto.</td>
                </tr>
            @endforelse
            <tr>
                <td>Laba/Rugi Ditangguhkan</td>
                <td class="numeric">{{ number_format($labaDitangguhkan, 2, ',', '.') }}</td>
            </tr>
             <tr>
                <td>Laba/Rugi Periode Ini</td>
                <td class="numeric">{{ number_format($labaRugiPeriodeIni, 2, ',', '.') }}</td>
            </tr>
            <tr class="total">
                <td>Total Aset Neto</td>
                <td class="numeric">{{ number_format($totalEkuitas, 2, ',', '.') }}</td>
            </tr>
            
            {{-- Total Gabungan --}}
            <tr class="total">
                <td>TOTAL LIABILITAS DAN ASET NETO</td>
                <td class="numeric">{{ number_format($totalKewajibanDanEkuitas, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

</body>
</html>