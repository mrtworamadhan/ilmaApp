<?php

namespace App\Filament\Yayasan\Resources\Payments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        $isYayasanUser = auth()->user()->school_id === null;

        return $table
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Nama Siswa')
                    ->searchable(),
                
                // Tampilkan kolom Sekolah HANYA jika Admin Yayasan
                TextColumn::make('school.name')
                    ->label('Sekolah')
                    ->badge()
                    ->hidden(!$isYayasanUser), 
                
                TextColumn::make('bill.feeCategory.name') // Ambil dari relasi
                    ->label('Pembayaran Untuk')
                    ->badge(),
                
                TextColumn::make('amount_paid')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge(),
                
                TextColumn::make('paid_at')
                    ->label('Tgl Bayar')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                
                TextColumn::make('creator.name') // Siapa yg input
                    ->label('Diinput Oleh')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('Filter Sekolah')
                    ->relationship('school', 'name')
                    ->hidden(!$isYayasanUser), 
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
