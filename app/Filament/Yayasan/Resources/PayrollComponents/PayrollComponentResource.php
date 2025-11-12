<?php

namespace App\Filament\Yayasan\Resources\PayrollComponents;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\PayrollComponents\Pages\CreatePayrollComponent;
use App\Filament\Yayasan\Resources\PayrollComponents\Pages\EditPayrollComponent;
use App\Filament\Yayasan\Resources\PayrollComponents\Pages\ListPayrollComponents;
use App\Filament\Yayasan\Resources\PayrollComponents\Schemas\PayrollComponentForm;
use App\Filament\Yayasan\Resources\PayrollComponents\Tables\PayrollComponentsTable;
use App\Models\Payroll\PayrollComponent;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PayrollComponentResource extends Resource
{
    use HasModuleAccess;
    protected static string $requiredModule = 'payroll';

    public static function canViewAny(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan']);
    }

    protected static ?string $model = PayrollComponent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;
    protected static string | UnitEnum | null $navigationGroup  = 'Payroll';

    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Komponen Gaji';

    protected static ?string $pluralModelLabel = 'Komponen Gaji';
    protected static ?string $recordTitleAttribute = 'name';
    public static function getEloquentQuery(): Builder
    {
        // Admin Yayasan hanya bisa lihat komponen milik yayasannya
        return parent::getEloquentQuery()
            ->where('foundation_id', Auth::user()->foundation_id);
    }
    public static function boot(): void
    {
        parent::boot();

        // Otomatis suntik foundation_id saat BUAT komponen
        static::creating(function ($component) {
            if (Auth::check()) {
                $component->foundation_id = Auth::user()->foundation_id;
            }
        });
    }

    public static function form(Schema $schema): Schema
    {
        return PayrollComponentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayrollComponentsTable::configure($table);
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
            'index' => ListPayrollComponents::route('/'),
            'create' => CreatePayrollComponent::route('/create'),
            'edit' => EditPayrollComponent::route('/{record}/edit'),
        ];
    }
}
