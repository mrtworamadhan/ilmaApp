<?php

namespace App\Filament\Yayasan\Resources\VendorDisbursements\Tables;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Pos\VendorDisbursement;
use App\Models\Pos\VendorLedger;
use App\Models\School;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                TextColumn::make('vendor.name')
                    ->label('Vendor/Kantin')
                    ->searchable(),
                TextColumn::make('school.name')
                    ->label('Sekolah')
                    ->searchable()
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
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('Sekolah')
                    ->options(
                        fn () => School::where('foundation_id', Auth::user()->foundation_id)
                                    ->pluck('name', 'id')
                    )
                    ->visible(fn () => Auth::user()->hasRole(['Admin Yayasan'])), // <-- Hanya untuk Admin Yayasan
                SelectFilter::make('status')
                    ->options([
                        'requested' => 'Diajukan',
                        'approved' => 'Disetujui',
                        'paid' => 'Dibayar',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->actions([
                ViewAction::make(),

                // --- KUNCI #2: AKSI APPROVE & BAYAR ---
                Action::make('approveAndPay')
                    ->label('Approve & Bayar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (VendorDisbursement $record) => $record->status === 'requested')
                    ->form([ // Minta info Akun untuk mencatat Expense
                        Select::make('payment_account_id')
                            ->label('Bayar Dari Akun Kas/Bank')
                            ->options(fn() => Account::where('foundation_id', Auth::user()->foundation_id)
                                            ->where('type', 'aktiva')->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Select::make('expense_account_id')
                            ->label('Catat Sebagai Beban di Akun')
                            ->options(fn() => Account::where('foundation_id', Auth::user()->foundation_id)
                                            ->where('type', 'Kewajiban')->pluck('name', 'id'))
                            ->helperText('Pilih akun "Utang Vendor" atau sejenisnya. Ini akan di-DEBIT.')
                            ->required()
                            ->searchable(),
                        Textarea::make('admin_notes')
                            ->label('Catatan (Opsional)')
                    ])
                    ->action(function (VendorDisbursement $record, array $data) {
                        try {
                            DB::transaction(function () use ($record, $data) {
                                // 1. Update status 'paid'
                                $record->update([
                                    'status' => 'paid',
                                    'processed_at' => now(),
                                    'processed_by' => Auth::id(),
                                    'notes' => $record->notes . "\n\nStaf Keuangan: " . $data['admin_notes'],
                                ]);

                                // 2. Create the Expense (Otomatis trigger ExpenseObserver)
                                // Sesuai struktur ExpenseForm, 'account_id' = Akun Beban (Debit)
                                // dan 'cash_account_id' = Akun Kas (Kredit)
                                Expense::create([
                                    'foundation_id' => $record->foundation_id,
                                    'school_id' => $record->school_id,
                                    'expense_account_id' => $data['expense_account_id'], // <-- INI PERBAIKANNYA
                                    'cash_account_id' => $data['payment_account_id'], // Akun Kas
                                    'amount' => $record->amount,
                                    'description' => "Pembayaran pencairan dana vendor: {$record->vendor->name} (Ref: {$record->id})",
                                    'date' => now(),
                                ]);
                                
                                // 3. Create VendorLedger 'debit' entry (KURANGI SALDO VENDOR)
                                $lastBalance = $record->vendor->ledgers()->latest()->first()?->balance_after ?? 0;
                                $newBalance = $lastBalance - $record->amount;
                                
                                VendorLedger::create([
                                    'foundation_id' => $record->foundation_id,
                                    'school_id' => $record->school_id,
                                    'vendor_id' => $record->vendor_id,
                                    'type' => 'debit', // <-- Saldo vendor berkurang
                                    'amount' => $record->amount,
                                    'balance_after' => $newBalance,
                                    'description' => "Pencairan dana dibayar (Ref ID: {$record->id})",
                                    'reference_id' => $record->id,
                                    'reference_type' => VendorDisbursement::class,
                                ]);
                            });

                            Notification::make()->title('Pencairan Berhasil Dibayar!')->success()->send();

                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal Membayar')->body($e->getMessage())->danger()->send();
                        }
                    }),
                
                // Aksi untuk menolak
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (VendorDisbursement $record) => $record->status === 'requested')
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->action(function (VendorDisbursement $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'processed_at' => now(),
                            'processed_by' => Auth::id(),
                            'notes' => $record->notes . "\n\nStaf Keuangan: " . $data['admin_notes'],
                        ]);
                        Notification::make()->title('Pengajuan Ditolak')->warning()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
