<?php

namespace App\Filament\Yayasan\Resources\VendorDisbursements;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\VendorDisbursements\Pages\CreateVendorDisbursement;
use App\Filament\Yayasan\Resources\VendorDisbursements\Pages\EditVendorDisbursement;
use App\Filament\Yayasan\Resources\VendorDisbursements\Pages\ListVendorDisbursements;
use App\Filament\Yayasan\Resources\VendorDisbursements\Schemas\VendorDisbursementForm;
use App\Filament\Yayasan\Resources\VendorDisbursements\Tables\VendorDisbursementsTable;
use App\Models\Pos\VendorDisbursement;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VendorDisbursementResource extends Resource
{
    use HasModuleAccess; // 4. Gunakan Trait
    protected static string $requiredModule = 'cashless';
    public static function canAccess(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }
    protected static ?string $model = VendorDisbursement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpOnSquare;

    protected static string | UnitEnum | null $navigationGroup  = 'Vendor Kantin';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Persetujuan Pencairan';

    protected static ?string $pluralModelLabel = 'Persetujuan Pencairan Vendor';
    public static function canCreate(): bool
    {
        return false;
    }
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();

        if ($user->hasRole('Admin Yayasan')) {
            return $query->where('foundation_id', $user->foundation_id);
        }

        return $query->where('school_id', $user->school_id);
    }

    public static function form(Schema $schema): Schema
    {
        return VendorDisbursementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorDisbursementsTable::configure($table);
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
            'index' => ListVendorDisbursements::route('/'),
            'create' => CreateVendorDisbursement::route('/create'),
            'edit' => EditVendorDisbursement::route('/{record}/edit'),
        ];
    }
}
