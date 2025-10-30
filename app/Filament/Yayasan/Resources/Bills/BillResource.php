<?php

namespace App\Filament\Yayasan\Resources\Bills;

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
    protected static ?string $model = Bill::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationLabel = 'Tagihan Biaya';
    protected static ?string $slug = 'tagihan';
    protected static string | UnitEnum | null $navigationGroup  = 'Manajemen Biaya';
    protected static ?int $navigationSort = 3;
    public static function getEloquentQuery(): Builder
    {
        // 1. Ambil query dasar (sudah di-scope ke Tenant/Yayasan)
        $query = parent::getEloquentQuery()
                    ->where('foundation_id', Filament::getTenant()->id);

        // 2. Cek apakah user ini level Sekolah?
        $userSchoolId = auth()->user()->school_id;
        
        if ($userSchoolId) {
            // 3. Jika ya, paksa query HANYA tampilkan tagihan
            // dari sekolah milik user tsb.
            $query->where('school_id', $userSchoolId);
        }

        // 4. Jika tidak (level Yayasan), kembalikan query langkah 1
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
