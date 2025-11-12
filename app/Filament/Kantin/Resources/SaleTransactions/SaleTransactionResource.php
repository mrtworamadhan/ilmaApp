<?php

namespace App\Filament\Kantin\Resources\SaleTransactions;

use App\Filament\Kantin\Resources\SaleTransactions\Pages\CreateSaleTransaction;
use App\Filament\Kantin\Resources\SaleTransactions\Pages\EditSaleTransaction;
use App\Filament\Kantin\Resources\SaleTransactions\Pages\ListSaleTransactions;
use App\Filament\Kantin\Resources\SaleTransactions\Pages\ViewSaleTransaction;
use App\Filament\Kantin\Resources\SaleTransactions\Schemas\SaleTransactionForm;
use App\Filament\Kantin\Resources\SaleTransactions\Schemas\SaleTransactionInfolist;
use App\Filament\Kantin\Resources\SaleTransactions\Tables\SaleTransactionsTable;
use App\Models\Pos\SaleTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SaleTransactionResource extends Resource
{
    protected static ?string $model = SaleTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Rekap Penjualan';

    protected static ?string $pluralModelLabel = 'Rekap Penjualan';

    public static function getEloquentQuery(): Builder
    {
        // Ambil vendor_id dari user yang sedang login
        $vendorId = Auth::user()->vendor?->id;

        // Terapkan scope query
        return parent::getEloquentQuery()->where('vendor_id', $vendorId);
    }
    

    public static function form(Schema $schema): Schema
    {
        return SaleTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SaleTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SaleTransactionsTable::configure($table);
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
            'index' => ListSaleTransactions::route('/'),
            'view' => ViewSaleTransaction::route('/{record}'),
        ];
    }
}
