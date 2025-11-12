<?php

namespace App\Filament\Kantin\Resources\Products;

use App\Filament\Kantin\Resources\Products\Pages\CreateProduct;
use App\Filament\Kantin\Resources\Products\Pages\EditProduct;
use App\Filament\Kantin\Resources\Products\Pages\ListProducts;
use App\Filament\Kantin\Resources\Products\Schemas\ProductForm;
use App\Filament\Kantin\Resources\Products\Tables\ProductsTable;
use App\Models\Pos\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;
    protected static ?string $modelLabel = 'Produk';
    protected static ?string $pluralModelLabel = 'Produk';

    protected static ?string $recordTitleAttribute = 'name';
    public static function getEloquentQuery(): Builder
    {
        $vendorId = Auth::user()->vendor?->id;

        return parent::getEloquentQuery()->where('vendor_id', $vendorId);
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
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
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
