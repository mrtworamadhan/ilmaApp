<?php

namespace App\Filament\Yayasan\Resources\PayrollComponents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Payroll\Payslip;

class PayrollComponentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Komponen')
                    ->searchable(),
                TextColumn::make('type')
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
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->label(''),
                DeleteAction::make()->label(''),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
