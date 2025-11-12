<?php

namespace App\Filament\Yayasan\Resources\SavingAccounts\Tables;

use App\Models\SavingAccount;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SavingAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.school.name')
                    ->label('Sekolah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account_number')
                    ->label('No. Rekening')
                    ->searchable(),
                TextColumn::make('balance')
                    ->label('Saldo')
                    ->numeric(locale: 'id')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('Sekolah')
                    ->relationship('school', 'name')
                    ->hidden(fn () => auth()->user()->school_id !== null), // Sembunyikan jika Admin Sekolah
            ])
            ->actions([

                Action::make('setor')
                    ->label('Setor Tunai')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    // Munculkan Modal Form
                    ->form([
                        TextInput::make('amount')
                            ->label('Jumlah Setor')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(1),
                        TextInput::make('description')
                            ->label('Keterangan')
                            ->required()
                            ->default('Setor tunai'),
                    ])
                    ->action(function (SavingAccount $record, array $data) {
                        // Logika saat tombol 'Submit' di modal diklik
                        $record->transactions()->create([
                            'foundation_id' => $record->foundation_id,
                            'type' => 'CREDIT', // Setor
                            'amount' => $data['amount'],
                            'description' => $data['description'],
                            'user_id' => Auth::id(),
                        ]);
                        Notification::make()->title('Setor tunai berhasil')->success()->send();
                    }),
                
                Action::make('tarik')
                    ->label('Tarik Tunai')
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    // Munculkan Modal Form
                    ->form([
                        TextInput::make('amount')
                            ->label('Jumlah Tarik')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(1)
                            // Validasi: tidak boleh tarik > saldo
                            ->maxValue(fn (SavingAccount $record) => $record->balance),
                        TextInput::make('description')
                            ->label('Keterangan')
                            ->required()
                            ->default('Tarik tunai'),
                    ])
                    ->action(function (SavingAccount $record, array $data) {
                        // Validasi saldo sekali lagi (untuk keamanan)
                        if ($data['amount'] > $record->balance) {
                            Notification::make()->title('Saldo tidak mencukupi!')->danger()->send();
                            return;
                        }
                        
                        $record->transactions()->create([
                            'foundation_id' => $record->foundation_id,
                            'type' => 'DEBIT', // Tarik
                            'amount' => $data['amount'],
                            'description' => $data['description'],
                            'user_id' => Auth::id(),
                        ]);
                        Notification::make()->title('Tarik tunai berhasil')->success()->send();
                    }),
                
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
