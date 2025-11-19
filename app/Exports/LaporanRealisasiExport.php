<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class LaporanRealisasiExport implements FromView, WithTitle
{
    protected $items;
    protected $academicYear;
    protected $departmentName;

    public function __construct($items, $academicYear, $departmentName)
    {
        $this->items = $items;
        $this->academicYear = $academicYear;
        $this->departmentName = $departmentName;
    }

    /**
    * @return \Illuminate\Contracts\View\View
    */
    public function view(): View
    {
        // Arahkan ke file Blade yang akan kita buat
        return view('exports.laporan-realisasi-anggaran', [
            'items' => $this->items,
            'academicYear' => $this->academicYear,
            'departmentName' => $this->departmentName,
        ]);
    }

    /**
    * @return string
    */
    public function title(): string
    {
        // Judul untuk sheet Excel
        return 'Laporan Realisasi Anggaran';
    }
}