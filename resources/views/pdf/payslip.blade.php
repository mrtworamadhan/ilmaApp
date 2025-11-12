<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Slip Gaji - {{ $payslip->teacher->full_name }} - {{ date('F', mktime(0, 0, 0, $payslip->month, 1)) }} {{ $payslip->year }}</title>
    
    {{-- 
        CSS WAJIB diletakkan di dalam <style> (inline) 
        agar bisa di-render oleh DomPDF.
    --}}
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 15px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            font-size: 18px;
        }
        .header h3 {
            margin: 5px 0;
            font-size: 16px;
        }
        .header p {
            margin: 0;
            font-size: 10px;
        }
        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 3px 5px;
            font-size: 12px;
        }
        
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .details-table th, 
        .details-table td {
            border: 1px solid #999;
            padding: 8px;
            text-align: left;
        }
        .details-table th {
            background-color: #f2f2f2;
            font-size: 13px;
        }
        .text-right {
            text-align: right;
        }
        .summary-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .net-pay-table {
            width: 100%;
            margin-top: 20px;
        }
        .net-pay-row td {
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
        }
        .signature-area {
            margin-top: 50px;
            width: 100%;
        }
        .signature {
            width: 45%;
            float: left;
            text-align: center;
        }
        .signature.right {
            float: right;
        }
        .signature p {
            margin-bottom: 60px;
        }
    </style>
</head>
<body>
    <div class="container">

        {{-- 1. KOP SURAT --}}
        <div class="header">
            {{-- Kita ambil data dari relasi school yang ada di payslip --}}
            <h2>{{ $payslip->school->foundation->name ?? 'Nama Yayasan' }}</h2>
            <h3>{{ $payslip->school->name ?? 'Nama Sekolah' }}</h3>
            <p>{{ $payslip->school->address ?? 'Alamat Sekolah' }}</p>
        </div>

        {{-- 2. JUDUL --}}
        <div class="title">
            SLIP GAJI KARYAWAN
        </div>

        {{-- 3. INFORMASI GURU & PERIODE --}}
        <table class="info-table">
            <tr>
                <td width="150px">Nama Karyawan</td>
                <td width="10px">:</td>
                <td>{{ $payslip->teacher->full_name }}</td>
                <td width="150px">Tanggal Pembayaran</td>
                <td width="10px">:</td>
                <td>{{ $payslip->created_at->format('d F Y') }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td width="10px">:</td>
                <td>Guru</td>
                <td width="150px">Periode Gaji</td>
                <td width="10px">:</td>
                <td>{{ date('F', mktime(0, 0, 0, $payslip->month, 1)) }} {{ $payslip->year }}</td>
            </tr>
        </table>

        {{-- 4. RINCIAN PENDAPATAN & POTONGAN --}}
        <table class="details-table">
            <thead>
                <tr>
                    <th width="60%">RINCIAN PENDAPATAN</th>
                    <th width="40%" class="text-right">JUMLAH (Rp)</th>
                </tr>
            </thead>
            <tbody>
                {{-- Loop semua 'allowance' (pendapatan) --}}
                @foreach($payslip->details->where('type', 'allowance') as $detail)
                    <tr>
                        <td>{{ $detail->component_name }}</td>
                        <td class="text-right">{{ number_format($detail->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="summary-row">
                    <td><b>Total Pendapatan (A)</b></td>
                    <td class="text-right"><b>{{ number_format($payslip->total_allowance, 0, ',', '.') }}</b></td>
                </tr>
            </tbody>
        </table>

        <table class="details-table">
            <thead>
                <tr>
                    <th width="60%">RINCIAN POTONGAN</th>
                    <th width="40%" class="text-right">JUMLAH (Rp)</th>
                </tr>
            </thead>
            <tbody>
                {{-- Loop semua 'deduction' (potongan) --}}
                @foreach($payslip->details->where('type', 'deduction') as $detail)
                    <tr>
                        <td>{{ $detail->component_name }}</td>
                        <td class="text-right">{{ number_format($detail->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="summary-row">
                    <td><b>Total Potongan (B)</b></td>
                    <td class="text-right"><b>{{ number_format($payslip->total_deduction, 0, ',', '.') }}</b></td>
                </tr>
            </tbody>
        </table>

        {{-- 5. GAJI BERSIH (TAKE HOME PAY) --}}
        <table class="net-pay-table">
            <tr class="net-pay-row">
                <td>GAJI BERSIH (A - B)</td>
                <td class="text-right">Rp {{ number_format($payslip->net_pay, 0, ',', '.') }}</td>
            </tr>
        </table>
        
        {{-- 6. TANDA TANGAN --}}
        <div class="signature-area">
            <div class="signature">
                <p>Bendahara,</p>
                <p>(______________________)</p>
            </div>
            <div class="signature right">
                <p>Diterima Oleh,</p>
                <p>({{ $payslip->teacher->full_name }})</p>
            </div>
        </div>

    </div>
</body>
</html>