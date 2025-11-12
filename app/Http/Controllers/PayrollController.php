<?php

namespace App\Http\Controllers;

use App\Models\Payroll\Payslip; 
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PayrollController extends Controller
{
    /**
     * Meng-generate dan men-download PDF slip gaji.
     * * @param  \App\Models\Payroll\Payslip  $payslip
     * @return \Illuminate\Http\Response
     */
    public function downloadPayslip(Payslip $payslip)
    {
        $payslip->load(['teacher', 'school.foundation', 'details']);

        $monthName = strtolower(Carbon::create()->month($payslip->month)->format('F'));
        $teacherName = Str::slug($payslip->teacher->full_name);
        $year = $payslip->year;
        $filename = "slip-gaji-{$teacherName}-{$monthName}-{$year}.pdf";

        $pdf = PDF::loadView('pdf.payslip', [
            'payslip' => $payslip
        ]);

        return $pdf->download($filename);
    }
}