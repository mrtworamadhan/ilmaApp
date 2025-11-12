<?php

namespace App\Filament\Yayasan\Resources\Teachers\RelationManagers;

use App\Filament\Yayasan\Resources\PayrollComponents\PayrollComponentResource;
use App\Models\Payroll\PayrollComponent;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PayrollsRelationManager extends RelationManager
{
    protected static string $relationship = 'payrolls';

    protected static ?string $title = 'Komponen Gaji';
    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Select::make('payroll_component_id')
                    ->label('Komponen Gaji')
                    ->options(function () {
                        // Ambil komponen yg foundation_id-nya sama dgn user admin
                        return PayrollComponent::where(
                            'foundation_id', Auth::user()->foundation_id)
                            ->pluck('name', key: 'id');
                    })
                    ->searchable()
                    ->required(),
                TextInput::make('amount')
                    ->label('Nominal (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id') // Kita tidak punya judul yg bagus, pakai ID
            ->columns([
                TextColumn::make('payrollComponent.name')
                    ->label('Nama Komponen')
                    ->searchable(),
                TextColumn::make('payrollComponent.type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'allowance' => 'success', // Pendapatan = Hijau
                        'deduction' => 'danger',  // Potongan = Merah
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'allowance' => 'Pendapatan',
                        'deduction' => 'Potongan',
                    }),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->label('Total Pendapatan')
                            ->money('IDR')
                            ->query(function ($query) {
                                // Kita 'join' tabel master-nya
                                return $query->join('payroll_components', 'employee_payrolls.payroll_component_id', '=', 'payroll_components.id')
                                             ->where('payroll_components.type', 'allowance'); // Baru kita filter
                            }),
                        Sum::make()
                            ->label('Total Potongan')
                            ->money('IDR')
                            ->query(function ($query) {
                                return $query->join('payroll_components', 'employee_payrolls.payroll_component_id', '=', 'payroll_components.id')
                                             ->where('payroll_components.type', 'deduction');
                            }),
                        ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $ownerRecord = $this->getOwnerRecord();                         
                        $data['foundation_id'] = $ownerRecord->foundation_id;
                        $data['school_id'] = $ownerRecord->school_id;
                        
                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
