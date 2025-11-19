<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class LaporanNeracaExport implements FromView, WithTitle
{
    // Properti untuk menampung data dari halaman
    protected $hasilAktiva;
    protected $totalAktiva;
    protected $hasilKewajiban;
    protected $totalKewajiban;
    protected $hasilEkuitas;
    protected $totalEkuitas;
    protected $labaDitangguhkan;
    protected $labaRugiPeriodeIni;
    protected $totalKewajibanDanEkuitas;
    protected $startDate;
    protected $endDate;
    protected $namaSekolah; // Tambahan untuk judul

    public function __construct(
        $hasilAktiva,
        $totalAktiva,
        $hasilKewajiban,
        $totalKewajiban,
        $hasilEkuitas,
        $totalEkuitas,
        $labaDitangguhkan,
        $labaRugiPeriodeIni,
        $totalKewajibanDanEkuitas,
        $startDate,
        $endDate,
        $namaSekolah // Tambahan untuk judul
    ) {
        // Assign semua data ke properti kelas ini
        $this->hasilAktiva = $hasilAktiva;
        $this->totalAktiva = $totalAktiva;
        $this->hasilKewajiban = $hasilKewajiban;
        $this->totalKewajiban = $totalKewajiban;
        $this->hasilEkuitas = $hasilEkuitas;
        $this->totalEkuitas = $totalEkuitas;
        $this->labaDitangguhkan = $labaDitangguhkan;
        $this->labaRugiPeriodeIni = $labaRugiPeriodeIni;
        $this->totalKewajibanDanEkuitas = $totalKewajibanDanEkuitas;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->namaSekolah = $namaSekolah;
    }

    /**
    * @return \Illuminate\Contracts\View\View
    */
    public function view(): View
    {
        // Arahkan ke file Blade yang akan kita buat
        // dan kirim semua data ke view tersebut
        return view('exports.laporan-neraca', [
            'hasilAktiva' => $this->hasilAktiva,
            'totalAktiva' => $this->totalAktiva,
            'hasilKewajiban' => $this->hasilKewajiban,
            'totalKewajiban' => $this->totalKewajiban,
            'hasilEkuitas' => $this->hasilEkuitas,
            'totalEkuitas' => $this->totalEkuitas,
            'labaDitangguhkan' => $this->labaDitangguhkan,
            'labaRugiPeriodeIni' => $this->labaRugiPeriodeIni,
            'totalKewajibanDanEkuitas' => $this->totalKewajibanDanEkuitas,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'namaSekolah' => $this->namaSekolah,
        ]);
    }

    /**
    * @return string
    */
    public function title(): string
    {
        // Judul untuk sheet Excel
        return 'Laporan Neraca';
    }
}