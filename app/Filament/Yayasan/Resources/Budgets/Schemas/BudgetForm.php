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
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

class BudgetForm
{
    public static function configure(Schema $schema): Schema
    {
        $foundationId = Auth::user()->foundation_id;
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Info Anggaran')
                        ->schema([
                            Select::make('department_id')
                                ->relationship(
                                    name: 'department', 
                                    titleAttribute: 'name',
                                    modifyQueryUsing: function (Builder $query) { 
                                        $user = auth()->user();
                                        $tenant = Filament::getTenant();

                                        $query->where('foundation_id', $tenant->id);

                                        if ($user->school_id !== null) {
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
                    Step::make('Akuntansi Dana Terikat (Opsional)')
                        ->description('Isi bagian ini HANYA jika ini adalah anggaran untuk dana terikat (misal: Dana BOS, Wakaf).')
                        
                        ->schema([
                            Select::make('cash_source_account_id')
                                ->label('Sumber Kas/Bank Terikat')
                                ->helperText('Pilih rekening bank spesifik tempat dana ini disimpan.')
                                ->options(
                                    Account::where('foundation_id', $foundationId)
                                        ->where('type', 'Aset') // Filter hanya Kas/Bank
                                        ->pluck('name', 'id')
                                )
                                ->searchable()
                                ->preload()
                                ->nullable(),

                            Select::make('restricted_fund_account_id')
                                ->label('Map ke Aset Neto Terikat')
                                ->helperText('Pilih "amplop" dana terikat yang sesuai.')
                                ->options(
                                    Account::where('foundation_id', $foundationId)
                                        ->where('type', 'Aset Neto') // Filter hanya Aset Neto
                                        ->where('name', 'like', '%Terikat%') // Tampilkan yg terikat saja
                                        ->pluck('name', 'id')
                                )
                                ->searchable()
                                ->preload()
                                ->nullable(), // Boleh kosong
                        ])->columns(2),
                    Step::make('Rincian Anggaran (POS)')
                        ->schema([
                            Repeater::make('items')
                                ->relationship()
                                ->label('Rincian Pos Anggaran')
                                ->schema([
                                    Select::make('account_id')
                                        ->label('Mata Anggaran (COA)')
                                        ->options(Account::where('type', 'beban')->pluck('name', 'id')) // <-- HANYA ambil akun BEBAN
                                        ->searchable()
                                        ->required()
                                        ->columnSpan(2),
                                    Select::make('account_id')
                                        ->label('Map ke Akun (COA)')
                                        ->options(function () {
                                            return Account::where('foundation_id', Auth::user()->foundation_id)
                                                        ->where('type', 'Beban')
                                                        ->pluck('name', 'id');
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->placeholder('Pilih Akun Beban Terkait')
                                        ->required()
                                        ->columnSpan(1),
                                    TextInput::make('description')
                                        ->label('Deskripsi')
                                        ->required(),
                                    TextInput::make('planned_amount')
                                        ->label('Jumlah Dianggarkan')
                                        ->numeric()
                                        ->mask(RawJs::make('$money($input)'))
                                        ->stripCharacters(',')
                                        ->prefix('Rp')
                                        ->required()
                                        ->columnSpan(1),
                                ])
                                ->columns(2)
                                ->live()
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
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->prefix('Rp')
                    ->readOnly(),
            ]);
    }
    public static function updateTotal(Get $get, Set $set): void
    {
        $items = $get('items'); 
        $total = 0;

        if (is_array($items)) {
            foreach ($items as $item) {
                $amount = $item['planned_amount'] ?? '0';
            
                $cleanAmount = preg_replace('/[^\d.]/', '', $amount);
                $cleanAmount = floatval($cleanAmount);
                
                $total += $cleanAmount;
            }
        }

        $set('total_planned_amount', $total);
    }
}
