<?php

namespace App\Filament\Yayasan\Resources\DisbursementRequests;

use App\Filament\Yayasan\Resources\DisbursementRequests\Pages\CreateDisbursementRequest;
use App\Filament\Yayasan\Resources\DisbursementRequests\Pages\EditDisbursementRequest;
use App\Filament\Yayasan\Resources\DisbursementRequests\Pages\ListDisbursementRequests;
use App\Filament\Yayasan\Resources\DisbursementRequests\Schemas\DisbursementRequestForm;
use App\Filament\Yayasan\Resources\DisbursementRequests\Tables\DisbursementRequestsTable;
use App\Models\BudgetItem;
use App\Models\DisbursementRequest;
use BackedEnum;
use UnitEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DisbursementRequestResource extends Resource
{
    protected static ?string $model = DisbursementRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;

    protected static ?string $recordTitleAttribute = 'name';
    protected static string| UnitEnum |null $navigationGroup = 'Anggaran';
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Pengajuan Pencairan';
    // public static function getEloquentQuery(): Builder
    // {
    //     // 1. Ambil query dasar (sudah di-scope ke Tenant/Yayasan)
    //     $query = parent::getEloquentQuery()
    //                 ->where('foundation_id', Filament::getTenant()->id);

    //     // 2. Cek apakah user ini level Sekolah?
    //     $userSchoolId = auth()->user()->school_id;
        
    //     if ($userSchoolId) {
    //         // 3. Jika ya, paksa query HANYA tampilkan siswa
    //         // dari sekolah milik user tsb.
    //         $query->where('school_id', $userSchoolId);
    //     }

    //     // 4. Jika tidak (level Yayasan), kembalikan query langkah 1
    //     return $query;
    // }

    public static function form(Schema $schema): Schema
    {
        return DisbursementRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DisbursementRequestsTable::configure($table);
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
            'index' => ListDisbursementRequests::route('/'),
            'create' => CreateDisbursementRequest::route('/create'),
            'edit' => EditDisbursementRequest::route('/{record}/edit'),
        ];
    }
}
