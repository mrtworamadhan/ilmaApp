<?php

namespace App\Filament\Admin\Resources\Foundations;

use App\Filament\Admin\Resources\Foundations\Pages\CreateFoundation;
use App\Filament\Admin\Resources\Foundations\Pages\EditFoundation;
use App\Filament\Admin\Resources\Foundations\Pages\ListFoundations;
use App\Filament\Admin\Resources\Foundations\Schemas\FoundationForm;
use App\Filament\Admin\Resources\Foundations\Tables\FoundationsTable;
use App\Models\Foundation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FoundationResource extends Resource
{
    protected static ?string $model = Foundation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return FoundationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FoundationsTable::configure($table);
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
            'index' => ListFoundations::route('/'),
            'create' => CreateFoundation::route('/create'),
            'edit' => EditFoundation::route('/{record}/edit'),
        ];
    }
}
