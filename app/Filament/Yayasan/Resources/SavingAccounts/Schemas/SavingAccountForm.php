<?php

namespace App\Filament\Yayasan\Resources\SavingAccounts\Schemas;

use App\Models\Student;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class SavingAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_id')
                    ->label('Siswa')
                    // Tampilkan HANYA siswa yang BELUM punya rekening tabungan
                    ->options(function (Get $get, ?Model $record) {
                        // Jika 'edit', tampilkan nama siswa yg sedang diedit
                        if ($record) {
                            return Student::where('id', $record->student_id)
                                ->pluck('name', 'id');
                        }
                        
                        // Jika 'create', filter siswa
                        $tenant = Filament::getTenant();
                        return Student::where('foundation_id', $tenant->id)
                            ->doesntHave('savingAccount') // <-- Kunci: 'savingAccount' adalah nama relasi HasOne
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->unique(ignoreRecord: true) // 1 siswa 1 rekening
                    ->disabledOn('edit'), // Jangan ubah siswa jika sudah dibuat
                
                TextInput::make('account_number')
                    ->label('Nomor Rekening')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->default(fn () => 'TAB-' . time()), // <-- Nomor rekening otomatis
                
                TextInput::make('balance')
                    ->label('Saldo Awal')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->required()
                    // Hanya bisa diisi saat 'create', tidak bisa diubah saat 'edit'
                    ->disabledOn('edit'),
            ]);
    }
}
