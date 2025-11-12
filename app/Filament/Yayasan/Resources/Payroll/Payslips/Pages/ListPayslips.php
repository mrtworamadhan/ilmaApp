<?php

namespace App\Filament\Yayasan\Resources\Payroll\Payslips\Pages;

use App\Filament\Yayasan\Resources\Payroll\Payslips\PayslipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPayslips extends ListRecords
{
    protected static string $resource = PayslipResource::class;
}
