<?php

namespace App\Filament\Yayasan\Resources\Budgets\Tables;

use App\Models\Budget;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BudgetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('department.name')
                    ->searchable(),
                TextColumn::make('academic_year')
                    ->searchable(),
                BadgeColumn::make('status')
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'REJECTED' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'PENDING' => 'heroicon-o-clock',
                        'APPROVED' => 'heroicon-o-check-circle',
                        'REJECTED' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->label('Status Pengajuan'),
                TextColumn::make('total_planned_amount')
                    ->numeric(locale: 'id')
                    ->sortable()
                    ->money('IDR'),
                
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),

                // 2. TAMBAHKAN GRUP TOMBOL AKSI INI
                ActionGroup::make([
                    Action::make('approve')
                        ->label('Setujui')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Budget $record) => $record->status === 'PENDING') // Hanya tampil jika PENDING
                        ->action(function (Budget $record) {
                            $record->update(['status' => 'APPROVED']);
                            Notification::make()->title('Anggaran Disetujui')->success()->send();
                        })
                        ->requiresConfirmation(), // Minta konfirmasi

                    Action::make('reject')
                        ->label('Tolak')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Budget $record) => $record->status === 'PENDING') // Hanya tampil jika PENDING
                        ->action(function (Budget $record) {
                            $record->update(['status' => 'REJECTED']);
                            Notification::make()->title('Anggaran Ditolak')->warning()->send();
                        })
                        ->requiresConfirmation(),
                    
                    Action::make('reset_to_pending')
                        ->label('Reset ke Pending')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('gray')
                        ->visible(fn (Budget $record) => $record->status !== 'PENDING') // Tampil jika SUDAH di-approve/reject
                        ->action(function (Budget $record) {
                            $record->update(['status' => 'PENDING']);
                            Notification::make()->title('Status Direset ke Pending')->info()->send();
                        })
                        ->requiresConfirmation(),
                ])
                // 3. KUNCI SELURUH GRUP INI HANYA UNTUK ADMIN YAYASAN
                ->visible(fn () => Auth::user()->hasRole('Admin Yayasan'))
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
