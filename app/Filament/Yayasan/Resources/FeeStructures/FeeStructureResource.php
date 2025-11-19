<?php

namespace App\Filament\Yayasan\Resources\FeeStructures;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\FeeStructures\Pages\CreateFeeStructure;
use App\Filament\Yayasan\Resources\FeeStructures\Pages\EditFeeStructure;
use App\Filament\Yayasan\Resources\FeeStructures\Pages\ListFeeStructures;
use App\Filament\Yayasan\Resources\FeeStructures\Schemas\FeeStructureForm;
use App\Filament\Yayasan\Resources\FeeStructures\Tables\FeeStructuresTable;
use App\Models\FeeStructure;
use BackedEnum;
use UnitEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FeeStructureResource extends Resource
{
    use HasModuleAccess;
    protected static string $requiredModule = 'finance';
    public static function canAccess(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }

    protected static ?string $model = FeeStructure::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationLabel = 'Aturan Biaya (Master)';
    protected static ?string $slug = 'manajemen-biaya';
    protected static string | UnitEnum | null $navigationGroup  = 'Manajemen Biaya';
    protected static ?int $navigationSort = 1;

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
        return FeeStructureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeeStructuresTable::configure($table);
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
            'index' => ListFeeStructures::route('/'),
            'create' => CreateFeeStructure::route('/create'),
            'edit' => EditFeeStructure::route('/{record}/edit'),
        ];
    }
}
