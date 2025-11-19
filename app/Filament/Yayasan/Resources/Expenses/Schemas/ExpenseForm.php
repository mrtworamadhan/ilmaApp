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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema; // <-- Koreksi 'use'
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;
class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        $isYayasanUser = auth()->user()->school_id === null;
        $userSchoolId = auth()->user()->school_id;
        
        $tipeBeban = 'Beban'; 
        $tipeAset = 'Aset'; 

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
                    ->hidden(!$isYayasanUser) 
                    ->disabled(fn (Get $get) => $get('disbursement_request_id') !== null) 
                    ->dehydrated(),
                
                Select::make('disbursement_request_id')
                    ->label('Terkait Pengajuan Pencairan? (Opsional)')
                    ->options(
                        DisbursementRequest::where('status', 'APPROVED')
                            ->with(['budgetItem.account']) 
                            ->get()
                            ->mapWithKeys(function ($request) {
                                $description = $request->budgetItem?->description 
                                    ?: "Pengajuan #{$request->id}"; 
                                
                                return [
                                    $request->id => $description
                                ];
                            })
                    )
                    ->searchable()
                    ->preload()
                    ->live() 
                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        if (blank($state)) {
                            $set('expense_account_id', null);
                            return;
                        }
                        
                        $disbursement = DisbursementRequest::with('budgetItem')
                                            ->find($state);
                        
                        if ($disbursement && $disbursement->budgetItem) {
                            $set('expense_account_id', $disbursement->budgetItem->account_id);
                        }
                    }),

                Select::make('expense_account_id')
                    ->label('Dibebankan ke Akun (Debit)')
                    ->required()
                    ->options(fn () => Account::where('foundation_id', Filament::getTenant()->id)
                                        ->where('type', $tipeBeban) 
                                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->helperText('Akun pengeluaran, misal: "Beban ATK", "Beban Gaji"')
                    ->disabled(fn (Get $get) => $get('disbursement_request_id') !== null) 
                    ->dehydrated(),

                Select::make('cash_account_id')
                    ->label('Diambil dari Akun (Kredit)')
                    ->required()
                    ->options(fn () => Account::where('foundation_id', Filament::getTenant()->id)
                                        ->where('type', $tipeAset) 
                                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->helperText('Akun sumber uang, misal: "Kas SD", "Bank Yayasan"')
                    ->disabled(fn (Get $get) => $get('disbursement_request_id') !== null && $get('cash_account_id') !== null)
                    ->dehydrated(),

                Textarea::make('description')
                    ->label('Keterangan')
                    ->required()
                    ->dehydrated()
                    ->columnSpanFull(),
                
                FileUpload::make('proof_file')
                    ->label('Bukti Nota / Invoice (Opsional)')
                    ->directory('expense-proofs')
                    ->columnSpanFull(),
            ]);
    }
}