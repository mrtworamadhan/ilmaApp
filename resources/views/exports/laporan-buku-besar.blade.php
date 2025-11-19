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
            padding: 4px;
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
            margin-bottom: 5px;
        }
        .details {
            font-size: 11px;
            margin-bottom: 10px;
        }
        .numeric {
            text-align: right;
        }
        .total {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">LAPORAN BUKU BESAR</div>
    <div class="subheader">{{ $schoolName }}</div>
    
    <div class="details">
        <strong>Akun:</strong> {{ $accountName }} <br>
        <strong>Periode:</strong> 
        {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMMM Y') }} s/d 
        {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMMM Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                
                {{-- Hanya tampilkan kolom Akun jika tidak memfilter 1 akun --}}
                @if(!$selectedAccount)
                    <th>Akun</th>
                @endif
                
                <th>Keterangan</th>
                <th class="numeric">Debit</th>
                <th class="numeric">Kredit</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalDebit = 0;
                $totalKredit = 0;
            @endphp
            @forelse($entries as $entry)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($entry->journal->date)->format('d-m-Y') }}</td>
                    
                    @if(!$selectedAccount)
                        <td>{{ $entry->account->name ?? '-' }}</td>
                    @endif

                    <td>{{ $entry->journal->description ?? '-' }}</td>
                    <td class="numeric">{{ number_format($entry->debit_amount ?? 0, 2, ',', '.') }}</td>
                    <td class="numeric">{{ number_format($entry->credit_amount ?? 0, 2, ',', '.') }}</td>
                </tr>
                @php
                    $totalDebit += $entry->debit_amount ?? 0;
                    $totalKredit += $entry->credit_amount ?? 0;
                @endphp
            @empty
                <tr>
                    <td colspan="{{ $selectedAccount ? 4 : 5 }}" style="text-align: center;">Tidak ada data.</td>
                </tr>
            @endforelse
            
            {{-- Baris Total --}}
            <tr class="total">
                <td colspan="{{ $selectedAccount ? 2 : 3 }}" style="text-align: right;">TOTAL</td>
                <td class="numeric">{{ number_format($totalDebit, 2, ',', '.') }}</td>
                <td class="numeric">{{ number_format($totalKredit, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

</body>
</html>