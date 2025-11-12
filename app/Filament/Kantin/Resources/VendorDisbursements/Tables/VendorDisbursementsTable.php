<?php

namespace App\Filament\Kantin\Resources\VendorDisbursements\Tables;

use App\Models\Pos\VendorDisbursement;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorDisbursementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tgl. Pengajuan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'requested' => 'warning',
                        'approved' => 'info',
                        'paid' => 'success',
                        'rejected' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'requested' => 'Diajukan',
                        'approved' => 'Disetujui',
                        'paid' => 'Dibayar',
                        'rejected' => 'Ditolak',
                    }),
                TextColumn::make('processed_at')
                    ->label('Tgl. Diproses')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('processor.name') // Tampilkan nama Staf Keuangan
                    ->label('Diproses Oleh')
                    ->default('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make()->label(''),
                EditAction::make()
                    // Hanya bisa edit jika status masih 'requested'
                    ->visible(fn (VendorDisbursement $record) => $record->status === 'requested')
                    ->label('Ubah'),    
                DeleteAction::make()
                    // Hanya bisa hapus jika status masih 'requested'
                    ->visible(fn (VendorDisbursement $record) => $record->status === 'requested')
                    ->label('Batalkan'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
