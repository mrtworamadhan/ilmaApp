<?php

namespace App\Filament\Yayasan\Resources\Payments;

use App\Filament\Yayasan\Resources\Payments\Pages\CreatePayment;
use App\Filament\Yayasan\Resources\Payments\Pages\EditPayment;
use App\Filament\Yayasan\Resources\Payments\Pages\ListPayments;
use App\Filament\Yayasan\Resources\Payments\Schemas\PaymentForm;
use App\Filament\Yayasan\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use Filament\Facades\Filament;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationLabel = 'Catat Pembayaran';
    protected static ?string $slug = 'pembayaran';
    protected static string | UnitEnum | null $navigationGroup  = 'Manajemen Keuangan';
    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        // 1. Ambil query dasar (di-scope ke Tenant/Yayasan)
        $query = parent::getEloquentQuery()
                    ->where('foundation_id', Filament::getTenant()->id);

        // 2. Cek user level Sekolah?
        $userSchoolId = auth()->user()->school_id;
        
        if ($userSchoolId) {
            // 3. Jika ya, paksa query HANYA tampilkan pembayaran
            // dari sekolah milik user tsb.
            $query->where('school_id', $userSchoolId);
        }

        return $query;
    }
    public static function form(Schema $schema): Schema
    {
        return PaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
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
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }
}
