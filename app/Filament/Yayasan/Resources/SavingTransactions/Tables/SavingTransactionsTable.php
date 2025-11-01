<?php

namespace App\Filament\Yayasan\Resources\SavingTransactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SavingTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                TextColumn::make('savingAccount.student.name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('savingAccount.account_number')
                    ->label('No. Rek')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'CREDIT' => 'success',
                        'DEBIT' => 'danger',
                    }),
                TextColumn::make('amount')
                    ->numeric(locale: 'id')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('user.name') // Admin yg input
                    ->label('Dicatat Oleh')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
