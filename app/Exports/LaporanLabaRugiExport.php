<?php

namespace App\Exports;

// 1. IMPORT SEMUA YANG KITA BUTUHKAN
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

// 2. GANTI 'FromView' MENJADI 'FromCollection', 'WithHeadings'
class LaporanLabaRugiExport implements FromCollection, WithHeadings, WithProperties, ShouldAutoSize, WithEvents
{
    protected $data;
    protected $rowCounters = []; // Untuk styling

    // Construct (untuk terima data) tetap sama
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Mendefinisikan Judul Kolom (Header)
     */
    public function headings(): array
    {
        return [
            'Keterangan',
            'Tanpa Pembatasan',
            'Dengan Pembatasan',
            'Total',
        ];
    }

    /**
     * Membangun data baris per baris (FromCollection)
     */
    public function collection(): Collection
    {
        $data = $this->data;
        $collection = collect();
        $this->rowCounters = []; // Reset counter
        $lastRow = 1; // Mulai dari baris 1 (setelah header)

        // --- 1. PENDAPATAN ---
        $collection->push(['Keterangan' => 'Pendapatan & Sumbangan']);
        $lastRow++;
        
        foreach($data['hasilPendapatanTidakTerikat'] as $akun) {
            if($akun->total > 0) {
                $collection->push([
                    'Keterangan' => '  ' . $akun->name,
                    'Tanpa Pembatasan' => $akun->total,
                    'Dengan Pembatasan' => 0,
                    'Total' => $akun->total
                ]);
                $lastRow++;
            }
        }
        foreach($data['hasilPendapatanTerikat'] as $akun) {
            if($akun->total > 0) {
                $collection->push([
                    'Keterangan' => '  ' . $akun->name,
                    'Tanpa Pembatasan' => 0,
                    'Dengan Pembatasan' => $akun->total,
                    'Total' => $akun->total
                ]);
                $lastRow++;
            }
        }
        
        $collection->push([
            'Keterangan' => '  Dana Dilepaskan dari Pembatasan',
            'Tanpa Pembatasan' => $data['totalPelepasanDana_Kredit'],
            'Dengan Pembatasan' => -$data['totalPelepasanDana_Debit'],
            'Total' => 0
        ]);
        $lastRow++;
        $this->rowCounters['subtotal_pendapatan'] = $lastRow; // Tandai baris subtotal

        $collection->push([
            'Keterangan' => 'Total Pendapatan & Pelepasan',
            'Tanpa Pembatasan' => $data['totalPendapatanTidakTerikat'] + $data['totalPelepasanDana_Kredit'],
            'Dengan Pembatasan' => $data['totalPendapatanTerikat'] - $data['totalPelepasanDana_Debit'],
            'Total' => $data['totalPendapatan']
        ]);
        $lastRow++;
        $collection->push([]); // Baris kosong
        $lastRow++;
        
        // --- 2. BEBAN ---
        $collection->push(['Keterangan' => 'Beban']);
        $lastRow++;
        $this->rowCounters['header_beban'] = $lastRow; // Tandai baris header beban
        
        if($data['hasilBeban']->isEmpty()){
            $collection->push(['Keterangan' => '  Tidak ada data beban.']);
            $lastRow++;
        } else {
            foreach($data['hasilBeban'] as $akun) {
                 if($akun->total > 0) {
                     $collection->push([
                        'Keterangan' => '  ' . $akun->name,
                        'Tanpa Pembatasan' => -$akun->total,
                        'Dengan Pembatasan' => 0,
                        'Total' => -$akun->total
                    ]);
                    $lastRow++;
                 }
            }
        }
        
        $this->rowCounters['subtotal_beban'] = $lastRow; // Tandai baris subtotal
        $collection->push([
            'Keterangan' => 'Total Beban',
            'Tanpa Pembatasan' => -$data['totalBeban'],
            'Dengan Pembatasan' => 0,
            'Total' => -$data['totalBeban']
        ]);
        $lastRow++;
        $collection->push([]); // Baris kosong
        $lastRow++;

        // --- 3. TOTAL AKHIR ---
        $this->rowCounters['grand_total'] = $lastRow; // Tandai baris grand total
        $collection->push([
            'Keterangan' => 'Total Kenaikan/(Penurunan) Aset Neto',
            'Tanpa Pembatasan' => $data['totalSurplusDefisit_TidakTerikat'],
            'Dengan Pembatasan' => $data['totalSurplusDefisit_Terikat'],
            'Total' => $data['totalPerubahanAsetNeto']
        ]);

        return $collection;
    }

    /**
     * (Opsional) Menambahkan metadata
     */
    public function properties(): array
    {
        return [
            'creator'        => 'ILMA APP',
            'title'          => 'Laporan Penghasilan Komprehensif',
            'company'        => $this->data['schoolName'] ?? auth()->user()->foundation->name,
        ];
    }

    /**
     * (Opsional) Menambahkan styling (Bold, border, dll)
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Style untuk Header (Baris 1)
                $event->sheet->getStyle('A1:D1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9ECEF']]
                ]);

                // Style untuk Subtotal & Grand Total
                $styleSubtotal = [
                    'font' => ['bold' => true],
                    'borders' => ['top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
                ];
                $styleGrandTotal = [
                    'font' => ['bold' => true, 'size' => 14],
                    'borders' => ['top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE]]
                ];
                $styleHeaderGrup = [
                    'font' => ['bold' => true, 'size' => 11]
                ];

                // Terapkan style ke baris yang sudah kita tandai
                $event->sheet->getStyle('A2')->applyFromArray($styleHeaderGrup);
                $event->sheet->getStyle('A' . $this->rowCounters['subtotal_pendapatan'] . ':D' . $this->rowCounters['subtotal_pendapatan'])->applyFromArray($styleSubtotal);
                $event->sheet->getStyle('A' . $this->rowCounters['header_beban'])->applyFromArray($styleHeaderGrup);
                $event->sheet->getStyle('A' . $this->rowCounters['subtotal_beban'] . ':D' . $this->rowCounters['subtotal_beban'])->applyFromArray($styleSubtotal);
                $event->sheet->getStyle('A' . $this->rowCounters['grand_total'] . ':D' . $this->rowCounters['grand_total'])->applyFromArray($styleGrandTotal);

                // Format Angka
                $lastRow = $this->rowCounters['grand_total'];
                $formatAngka = '_(* #,##0.00_);_(* (#,##0.00);_(* "-"??_);_(@_)';
                $event->sheet->getStyle('B2:D' . $lastRow)->getNumberFormat()->setFormatCode($formatAngka);
            },
        ];
    }
}