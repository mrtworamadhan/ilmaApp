<?php

namespace App\Filament\Yayasan\Resources\Schools;

use App\Filament\Yayasan\Resources\Schools\Pages\CreateSchool;
use App\Filament\Yayasan\Resources\Schools\Pages\EditSchool;
use App\Filament\Yayasan\Resources\Schools\Pages\ListSchools;
use App\Filament\Yayasan\Resources\Schools\Schemas\SchoolForm;
use App\Filament\Yayasan\Resources\Schools\Tables\SchoolsTable;
use App\Models\School;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder; // <-- 1. TAMBAHKAN 'USE' INI
use Filament\Facades\Filament;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingLibrary;
    protected static string | UnitEnum | null $navigationGroup  = 'Pengaturan';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Manajemen Sekolah';
    protected static ?string $slug = 'sekolah';
    protected static ?string $recordTitleAttribute = 'name';
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $query->where('foundation_id', Filament::getTenant()->id);

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return SchoolForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolsTable::configure($table);
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
            'index' => ListSchools::route('/'),
            'create' => CreateSchool::route('/create'),
            'edit' => EditSchool::route('/{record}/edit'),
        ];
    }
}
