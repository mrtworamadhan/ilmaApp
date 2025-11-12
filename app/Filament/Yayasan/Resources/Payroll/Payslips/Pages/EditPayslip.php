<?php

namespace App\Filament\Yayasan\Resources\Payroll\Payslips\Pages;

use App\Filament\Yayasan\Resources\Payroll\Payslips\PayslipResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPayslip extends EditRecord
{
    protected static string $resource = PayslipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
