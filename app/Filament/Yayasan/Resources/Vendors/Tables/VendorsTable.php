<?php

namespace App\Filament\Yayasan\Resources\Vendors\Tables;

use App\Models\Pos\VendorDisbursement;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Vendor')
                    ->searchable(),
                TextColumn::make('school.name')
                    ->label('Sekolah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Nama Kasir')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('current_balance')
                    ->label('Saldo Vendor Saat Ini')
                    ->money('IDR')
                    ->getStateUsing(function (\App\Models\Pos\Vendor $record): float {
                        return $record->ledgers()
                                    ->latest()
                                    ->first()?->balance_after ?? 0;
                    })
                    ->color('info') 
                    ->weight('bold'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
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
