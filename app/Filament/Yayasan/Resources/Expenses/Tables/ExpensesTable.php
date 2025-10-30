<?php

namespace App\Filament\Yayasan\Resources\Expenses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        $isYayasanUser = auth()->user()->school_id === null;
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tgl Pengeluaran')
                    ->date('d M Y')
                    ->sortable(),
                
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->wrap(),
                
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),
                
                TextColumn::make('school.name')
                    ->label('Unit Sekolah')
                    ->badge()
                    ->hidden(!$isYayasanUser), 
                
                TextColumn::make('expenseAccount.name')
                    ->label('Akun Beban (Debit)')
                    ->badge(),
                
                TextColumn::make('cashAccount.name')
                    ->label('Akun Kas (Kredit)')
                    ->badge(),
                
                IconColumn::make('proof_file')
                    ->label('Bukti')
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    ->default(false),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('Filter Sekolah')
                    ->relationship('school', 'name')
                    ->hidden(!$isYayasanUser),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
