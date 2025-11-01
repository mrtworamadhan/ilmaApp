<?php

namespace App\Filament\Yayasan\Resources\Expenses\Schemas;

use App\Models\Account;
use App\Models\DisbursementRequest;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        $isYayasanUser = auth()->user()->school_id === null;
        $userSchoolId = auth()->user()->school_id;
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('Tanggal Pengeluaran')
                    ->default(now())
                    ->required(),

                TextInput::make('amount')
                    ->label('Nominal (Rp)')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),

                // 1. Dropdown Sekolah (Hanya untuk Admin Yayasan)
                Select::make('school_id')
                    ->label('Pengeluaran untuk Sekolah (Opsional)')
                    ->helperText('Kosongkan jika ini pengeluaran level Yayasan')
                    ->relationship(
                        name: 'school',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => 
                            $query->where('foundation_id', Filament::getTenant()->id)
                    )
                    ->searchable()
                    ->preload()
                    ->hidden(!$isYayasanUser), // Sembunyikan jika bukan Admin Yayasan

                Select::make('disbursement_request_id')
                    ->label('Terkait Pengajuan Pencairan?')
                    ->options(
                        DisbursementRequest::where('status', 'APPROVED') // <-- Hanya tampilkan yg sudah disetujui
                            ->pluck('id', 'id') // Tampilkan ID atau deskripsi
                            // Anda bisa kustomisasi format opsi ini
                    )
                    ->searchable()
                    ->nullable(),
                    // 2. Field Tersembunyi (Hanya untuk Admin Sekolah)
                Hidden::make('school_id')
                    ->default($userSchoolId)
                    ->hidden($isYayasanUser), // Sembunyikan jika Admin Yayasan
                
                // 3. Dropdown Akun Beban
                Select::make('expense_account_id')
                    ->label('Dibebankan ke Akun (Debit)')
                    ->required()
                    ->options(fn () => Account::where('foundation_id', Filament::getTenant()->id)
                                            // Hanya tampilkan akun BEBAN
                                            ->where('type', 'beban') 
                                            ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->helperText('Akun pengeluaran, misal: "Beban ATK", "Beban Gaji"'),

                // 4. Dropdown Akun Kas/Bank
                Select::make('cash_account_id')
                    ->label('Diambil dari Akun (Kredit)')
                    ->required()
                    ->options(fn () => Account::where('foundation_id', Filament::getTenant()->id)
                                            // Hanya tampilkan akun AKTIVA (Kas/Bank)
                                            ->where('type', 'aktiva') 
                                            ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->helperText('Akun sumber uang, misal: "Kas SD", "Bank Yayasan"'),

                Textarea::make('description')
                    ->label('Keterangan')
                    ->required()
                    ->columnSpanFull(),
                
                FileUpload::make('proof_file')
                    ->label('Bukti Nota / Invoice (Opsional)')
                    ->directory('expense-proofs')
                    ->columnSpanFull(),
            ]);
    }
}
