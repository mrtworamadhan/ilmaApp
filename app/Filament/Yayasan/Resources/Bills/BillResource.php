<?php

namespace App\Filament\Yayasan\Resources\Bills;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\Bills\Pages\CreateBill;
use App\Filament\Yayasan\Resources\Bills\Pages\EditBill;
use App\Filament\Yayasan\Resources\Bills\Pages\ListBills;
use App\Filament\Yayasan\Resources\Bills\Schemas\BillForm;
use App\Filament\Yayasan\Resources\Bills\Tables\BillsTable;
use App\Models\Bill;
use Filament\Facades\Filament;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BillResource extends Resource
{
    use HasModuleAccess;
    protected static string $requiredModule = 'finance';
    public static function canViewAny(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Manajemen Biaya';
    }
    
    protected static ?string $model = Bill::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationLabel = 'Tagihan Biaya';
    protected static ?string $slug = 'tagihan';
    protected static string | UnitEnum | null $navigationGroup  = 'Manajemen Biaya';
    protected static ?int $navigationSort = 3;
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
                    ->where('foundation_id', Filament::getTenant()->id);

        $userSchoolId = auth()->user()->school_id;
        
        if ($userSchoolId) {
            $query->where('school_id', $userSchoolId);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return BillForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillsTable::configure($table);
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
            'index' => ListBills::route('/'),
            'create' => CreateBill::route('/create'),
            'edit' => EditBill::route('/{record}/edit'),
        ];
    }
}
