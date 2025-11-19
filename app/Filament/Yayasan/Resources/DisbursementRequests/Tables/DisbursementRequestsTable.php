<?php

namespace App\Filament\Yayasan\Resources\DisbursementRequests\Tables;

use App\Filament\Yayasan\Resources\Expenses\ExpenseResource;
use App\Models\DisbursementRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DisbursementRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
            TextColumn::make('budgetItem.description')
                ->label('Pos Anggaran')
                ->searchable()
                ->wrap(), // <-- Lebih baik pakai wrap
            TextColumn::make('requester.name')
                ->label('Diajukan Oleh')
                ->searchable(),
            TextColumn::make('requested_amount')
                ->label('Jumlah Diajukan')
                ->numeric(locale: 'id')
                ->money('IDR'),
            TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'PENDING' => 'warning',
                    'APPROVED' => 'success',
                    'REJECTED' => 'danger',
                    'DISBURSED' => 'info',
                    default => 'gray',
                }),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->label('laporan')
                    ->visible(fn (DisbursementRequest $record): bool => 
                        $record->status === 'APPROVED' && $record->requester_id === Auth::id()
                    ),

                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation() // <-- Minta konfirmasi
                    ->modalHeading('Setujui Pengajuan?')
                    ->action(function (DisbursementRequest $record) {
                        $record->update([
                            'status' => 'APPROVED',
                            'approver_id' => Auth::id(),
                        ]);
                        Notification::make()->title('Pengajuan disetujui')->success()->send();
                    })
                    ->visible(function (DisbursementRequest $record): bool {
                        $userHasAccess = auth()->user()?->hasRole(['Admin Sekolah', 'Admin Yayasan']) ?? false;
                        return $record->status === 'PENDING' && $userHasAccess;
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Pengajuan?')
                    ->action(function (DisbursementRequest $record) {
                        $record->update([
                            'status' => 'REJECTED',
                            'approver_id' => Auth::id(),
                        ]);
                        Notification::make()->title('Pengajuan ditolak')->danger()->send();
                    })
                    ->visible(function (DisbursementRequest $record): bool {
                        $userHasAccess = auth()->user()?->hasRole(['Admin Sekolah', 'Admin Yayasan']) ?? false;                        // Tampilkan HANYA jika status PENDING DAN user punya hak
                        return $record->status === 'PENDING' && $userHasAccess;
                    }),
                Action::make('record_realization')
                    ->label('Catat Realisasi')
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    // Hanya tampil jika:
                    // 1. Statusnya APPROVED
                    // 2. User adalah Admin Yayasan
                    ->visible(fn (DisbursementRequest $record) => 
                        $record->status === 'APPROVED' && 
                        Auth::user()->hasRole(['Admin Sekolah', 'Admin Yayasan'])
                    )
                    // Arahkan ke Form Buat Expense sambil membawa ID ajuan
                    ->url(fn (DisbursementRequest $record): string => 
                        ExpenseResource::getUrl('create', ['disbursement_request_id' => $record->id])
                    ),

            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
