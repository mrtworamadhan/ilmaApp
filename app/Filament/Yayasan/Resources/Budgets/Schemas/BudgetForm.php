<?php

namespace App\Filament\Yayasan\Resources\Budgets\Schemas;

use App\Models\Account;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class BudgetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Info Anggaran')
                        ->schema([
                            Select::make('department_id')
                                ->relationship(
                                    name: 'department', 
                                    titleAttribute: 'name',
                                    // 'Query ribet' ini WAJIB untuk memfilter list
                                    modifyQueryUsing: function (Builder $query) { 
                                        $user = auth()->user();
                                        $tenant = Filament::getTenant();

                                        $query->where('foundation_id', $tenant->id);

                                        // Jika user adalah Admin Sekolah (bukan Yayasan)
                                        if ($user->school_id !== null) {
                                            // Filter hanya untuk departemen sekolahnya
                                            $query->where('school_id', $user->school_id);
                                        }
                                        
                                        return $query;
                                    }
                                )
                                
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('academic_year')
                                ->label('Tahun Ajaran')
                                ->placeholder('Contoh: 2024/2025')
                                ->required(),
                        ]),

                    Step::make('Rincian Anggaran')
                        ->schema([
                            Repeater::make('items') // <-- Nama relasi 'items'
                                ->relationship()
                                ->label('Rincian Pos Anggaran')
                                ->schema([
                                    Select::make('account_id')
                                        ->label('Mata Anggaran (COA)')
                                        ->options(Account::where('type', 'beban')->pluck('name', 'id')) // <-- HANYA ambil akun BEBAN
                                        ->searchable()
                                        ->required(),
                                    TextInput::make('description')
                                        ->label('Deskripsi')
                                        ->required(),
                                    TextInput::make('planned_amount')
                                        ->label('Jumlah Dianggarkan')
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->required(),
                                ])
                                ->columns(3)
                                ->live()
                                // Hitung total otomatis
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::updateTotal($get, $set);
                                })
                                ->deleteAction(
                                    fn (Action $action) => $action->after(fn (Get $get, Set $set) => self::updateTotal($get, $set)),
                                ),
                        ]),
                ])->columnSpanFull(),

                TextInput::make('total_planned_amount')
                    ->label('Total Anggaran')
                    ->numeric()
                    ->prefix('Rp')
                    ->readOnly(), // <-- Readonly, diisi oleh Repeater
            ]);
    }
    public static function updateTotal(Get $get, Set $set): void
    {
        $items = $get('items'); // Ambil semua item dari repeater
        $total = 0;

        if (is_array($items)) {
            foreach ($items as $item) {
                $total += floatval($item['planned_amount'] ?? 0);
            }
        }

        $set('total_planned_amount', $total);
    }
}
