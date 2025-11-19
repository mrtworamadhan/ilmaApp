<?php

namespace App\Filament\Yayasan\Resources\FeeCategories;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\FeeCategories\Pages\CreateFeeCategory;
use App\Filament\Yayasan\Resources\FeeCategories\Pages\EditFeeCategory;
use App\Filament\Yayasan\Resources\FeeCategories\Pages\ListFeeCategories;
use App\Filament\Yayasan\Resources\FeeCategories\Schemas\FeeCategoryForm;
use App\Filament\Yayasan\Resources\FeeCategories\Tables\FeeCategoriesTable;
use App\Models\FeeCategory;
use BackedEnum;
use UnitEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FeeCategoryResource extends Resource
{
    use HasModuleAccess;
    protected static string $requiredModule = 'finance';
    public static function canAccess(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }

    protected static ?string $model = FeeCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Tag;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationLabel = 'Kategori Biaya';
    protected static ?string $slug = 'kategori-biaya';
    protected static string | UnitEnum | null $navigationGroup  = 'Manajemen Biaya';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        // Otomatis filter data berdasarkan Yayasan yang login
        return parent::getEloquentQuery()
            ->where('foundation_id', Filament::getTenant()->id);
    }

    public static function form(Schema $schema): Schema
    {
        return FeeCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeeCategoriesTable::configure($table);
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
            'index' => ListFeeCategories::route('/'),
            'create' => CreateFeeCategory::route('/create'),
            'edit' => EditFeeCategory::route('/{record}/edit'),
        ];
    }
}
