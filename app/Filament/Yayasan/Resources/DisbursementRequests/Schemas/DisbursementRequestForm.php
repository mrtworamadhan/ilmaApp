<?php

namespace App\Filament\Yayasan\Resources\DisbursementRequests\Schemas;

// 1. TAMBAHKAN USE STATEMENTS INI
use App\Models\BudgetItem;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\RawJs;
use Illuminate\Support\Number;
// --------------------------

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DisbursementRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                
                Select::make('budget_item_id')
                    ->label('Pilih Pos Anggaran')
                    ->relationship(
                        name: 'budgetItem',
                        // 2. Koreksi: 'titleAttribute' dari migrasi adalah 'description'
                        // (Nama di migrasi: $table->string('description');)
                        titleAttribute: 'description', 
                        
                        // ===================================================
                        // LOGIKA QUERY ASLI ANDA YANG SUDAH BENAR 100%
                        // ===================================================
                        modifyQueryUsing: function (Builder $query) {
                            $user = Auth::user();
                            
                            if (is_null($user->department_id)) {
                                return $query->whereHas('budget', function (Builder $q) use ($user) {
                                    $q->where('status', 'APPROVED');
                                    if ($user->school_id) {
                                        $q->whereHas('department', fn(Builder $dq) => $dq->where('school_id', $user->school_id));
                                    }
                                });
                            }
                            
                            return $query->whereHas('budget', function (Builder $q) use ($user) {
                                $q->where('department_id', $user->department_id)
                                  ->where('status', 'APPROVED');
                            });
                        }
                    )
                    ->searchable()
                    ->preload() 
                    ->required()
                    ->hiddenOn('edit')
                    
                    // 3. TAMBAHKAN live() agar reactive
                    ->live() 
                    
                    // 4. TAMBAHKAN afterStateUpdated dengan LOGIKA YANG BENAR
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        if (blank($state)) {
                            $set('budget_available', 0);
                            return;
                        }
                        
                        // Eager load relasi 'expenses'
                        $budgetItem = BudgetItem::with('expenses')->find($state);
                        if (!$budgetItem) {
                            $set('budget_available', 0);
                            return;
                        }

                        // INI QUERY YANG BENAR (SESUAI MODEL ANDA)
                        $totalBudget = $budgetItem->planned_amount;
                        // Menjalankan relasi 'expenses' dan menjumlahkan 'amount'
                        $totalRealisasi = $budgetItem->expenses->sum('amount'); 
                        
                        $sisaBudget = $totalBudget - $totalRealisasi;
                        
                        $set('budget_available', $sisaBudget);
                    }),
                
                // 5. TAMBAHKAN Field Read-Only untuk Sisa Anggaran
                TextInput::make('budget_available')
                    ->label('Sisa Anggaran yang Tersedia')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->prefix('Rp')
                    ->numeric()
                    ->formatStateUsing(fn (?string $state) => Number::format($state ?? 0, 0, 0, 'id'))
                    ->disabled() 
                    ->dehydrated(false)
                    ->hiddenOn('edit'),

                TextInput::make('requested_amount')
                    ->label('Jumlah Diajukan')
                    ->numeric()
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->prefix('Rp')
                    ->required()
                    ->hiddenOn('edit')
                    
                    // 6. TAMBAHKAN Validasi maxValue
                    ->maxValue(function (Get $get) {
                        $sisa = $get('budget_available');
                        return $sisa > 0 ? $sisa : 0; 
                    })
                    ->helperText('Jumlah tidak boleh melebihi Sisa Anggaran.'),

                // Field Anda sebelumnya (tetap ada)
                FileUpload::make('realization_attachment')
                    ->label('Upload Nota / Laporan Realisasi')
                    ->directory('realisasi')
                    ->nullable()
                    ->visibleOn('edit'),
                TextInput::make('realization_amount')
                    ->label('Jumlah Realisasi (sesuai nota)')
                    ->numeric()
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->prefix('Rp')
                    ->nullable()
                    ->visibleOn('edit'),
            ]);
    }
}