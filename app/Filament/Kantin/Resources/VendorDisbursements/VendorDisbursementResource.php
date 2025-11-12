<?php

namespace App\Filament\Kantin\Resources\VendorDisbursements;

use App\Filament\Kantin\Resources\VendorDisbursements\Pages\CreateVendorDisbursement;
use App\Filament\Kantin\Resources\VendorDisbursements\Pages\EditVendorDisbursement;
use App\Filament\Kantin\Resources\VendorDisbursements\Pages\ListVendorDisbursements;
use App\Filament\Kantin\Resources\VendorDisbursements\Schemas\VendorDisbursementForm;
use App\Filament\Kantin\Resources\VendorDisbursements\Tables\VendorDisbursementsTable;
use App\Models\Pos\VendorDisbursement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\Pos\VendorLedger;

class VendorDisbursementResource extends Resource
{
    protected static ?string $model = VendorDisbursement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Pencairan Dana';

    protected static ?string $pluralModelLabel = 'Pencairan Dana';
    public static function getEloquentQuery(): Builder
    {
        // Ambil vendor_id dari user yang sedang login
        $vendorId = Auth::user()->vendor?->id;

        // Terapkan scope query
        return parent::getEloquentQuery()->where('vendor_id', $vendorId);
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
