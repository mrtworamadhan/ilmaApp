<?php

namespace App\Filament\Yayasan\Resources\DisbursementRequests;

use App\Filament\Traits\HasModuleAccess;
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
    use HasModuleAccess;
    protected static string $requiredModule = 'finance';
    public static function canAccess(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah', 'Kepala Bagian']);
    }
    
    protected static ?string $model = DisbursementRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;

    protected static ?string $recordTitleAttribute = 'name';
    protected static string| UnitEnum |null $navigationGroup = 'Anggaran';
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Pengajuan Pencairan';
    
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $foundationId = Filament::getTenant()->id;

        // 1. Ambil query dasar (selalu di-scope ke Yayasan/Tenant)
        $query = parent::getEloquentQuery()
                    ->where('foundation_id', $foundationId);

        // 2. CEK LEVEL 1 (Paling Spesifik): KEPALA BAGIAN
        // (Logic ini SUDAH BENAR)
        if ($user->hasRole('Kepala Bagian') && $user->department_id) {
            
            return $query->whereHas('budgetItem.budget', function (Builder $q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }

        // 3. CEK LEVEL 2: ADMIN SEKOLAH
        // (Ini hanya berjalan jika user BUKAN Kepala Bagian)
        if ($user->hasRole(['Admin Sekolah']) && $user->school_id) {
            
            // V-- INI DIA PERBAIKANNYA (LOGIC "LOMPAT" 4 TABEL) --V

            // Tampilkan Pengajuan...
            return $query->whereHas('budgetItem.budget.department', function (Builder $q) use ($user) {
                // ...yang departemen-nya punya school_id yang sama dengan user
                $q->where('school_id', $user->school_id);
            });

            // ^-- BATAS AKHIR PERBAIKAN --^
        }
        
        // 4. LEVEL 3: ADMIN YAYASAN
        // (Jika bukan keduanya, kembalikan semua data di yayasannya)
        return $query;
    }
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
