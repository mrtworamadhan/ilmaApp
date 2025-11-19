<?php

namespace App\Filament\Yayasan\Resources\Payroll\Payslips;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\Payroll\Payslips\Pages\CreatePayslip;
use App\Filament\Yayasan\Resources\Payroll\Payslips\Pages\EditPayslip;
use App\Filament\Yayasan\Resources\Payroll\Payslips\Pages\ListPayslips;
use App\Filament\Yayasan\Resources\Payroll\Payslips\Schemas\PayslipForm;
use App\Filament\Yayasan\Resources\Payroll\Payslips\Tables\PayslipsTable;
use App\Models\Payroll\Payslip;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PayslipResource extends Resource
{
    use HasModuleAccess;
    protected static string $requiredModule = 'payroll';

    public static function canAccess(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan']);
    }

    protected static ?string $model = Payslip::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static string | UnitEnum | null $navigationGroup  = 'Payroll';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Daftar Slip Gaji';
    protected static ?string $pluralModelLabel = 'Daftar Slip Gaji';
    protected static ?int $navigationSort = 3;
    public static function canCreate(): bool
    {
        return false;
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('foundation_id', Auth::user()->foundation_id)
            ->with(['teacher']);
    }

    public static function form(Schema $schema): Schema
    {
        return PayslipForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayslipsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayslips::route('/'),
            'create' => CreatePayslip::route('/create'),
            'edit' => EditPayslip::route('/{record}/edit'),
        ];
    }
}
