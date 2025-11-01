<?php

namespace App\Filament\Yayasan\Resources\Students\Tables;

use App\Services\XenditService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        $isYayasanUser = auth()->user()->school_id === null;
        return $table
            ->columns([
                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                
                // Tampilkan kolom Sekolah HANYA jika Admin Yayasan
                TextColumn::make('school.name')
                    ->label('Sekolah')
                    ->badge()
                    ->hidden(!$isYayasanUser), // Sembunyikan jika Admin Sekolah
                
                TextColumn::make('schoolClass.name')
                    ->label('Kelas')
                    ->badge(),
                    
                TextColumn::make('va_number')
                    ->label('Virtual Account (VA)')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'warning',
                        'active' => 'success',
                        'inactive' => 'gray',
                        'graduated' => 'primary',
                    }),
            ])
            
            ->filters([
                \Filament\Tables\Filters\Filter::make('no_va')
                    ->label('Siswa tanpa Virtual Account')
                    ->query(fn ($query) => $query->whereNull('va_number')),
            ])

            ->recordActions([
                EditAction::make(),
                // Action untuk generate VA per siswa
                Action::make('generateVA')
                    ->label('Generate VA')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->action(function ($record) {
                        try {
                            $vaNumber = XenditService::makeVA($record);
                            
                            if ($vaNumber) {
                                \Filament\Notifications\Notification::make()
                                    ->title('VA Berhasil Dibuat!')
                                    ->body("VA Number: {$vaNumber}")
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Gagal Membuat VA')
                                    ->body('Silakan coba lagi atau periksa log sistem')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->hidden(fn ($record) => !is_null($record->va_number)) // Sembunyikan jika sudah ada VA
                    ->requiresConfirmation()
                    ->modalHeading('Generate Virtual Account')
                    ->modalDescription('Apakah Anda yakin ingin membuat Virtual Account untuk siswa ini?')
                    ->modalSubmitActionLabel('Ya, Generate VA'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    // Bulk Action untuk generate VA multiple students
                    BulkAction::make('generateVA')
                        ->label('Generate VA untuk Siswa Terpilih')
                        ->icon('heroicon-o-credit-card')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $successCount = 0;
                            $failedCount = 0;
                            $results = [];
                            
                            foreach ($records as $record) {
                                // Skip jika sudah ada VA
                                if (!is_null($record->va_number)) {
                                    $failedCount++;
                                    $results[] = "{$record->name}: Sudah memiliki VA";
                                    continue;
                                }
                                
                                try {
                                    $vaNumber = XenditService::makeVA($record);
                                    
                                    if ($vaNumber) {
                                        $successCount++;
                                        $results[] = "{$record->name}: ✅ {$vaNumber}";
                                    } else {
                                        $failedCount++;
                                        $results[] = "{$record->name}: ❌ Gagal generate";
                                    }
                                } catch (\Exception $e) {
                                    $failedCount++;
                                    $results[] = "{$record->name}: ❌ Error - {$e->getMessage()}";
                                }
                            }
                            
                            // Tampilkan hasil summary
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Generate VA Selesai')
                                ->body("
                                    Berhasil: {$successCount} siswa
                                    Gagal: {$failedCount} siswa
                                ")
                                ->success()
                                ->send();
                            
                            // Log detail results untuk debugging
                            \Log::info('Bulk VA Generation Results:', $results);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Generate Virtual Account Massal')
                        ->modalDescription('
                            Apakah Anda yakin ingin membuat Virtual Account untuk SEMUA siswa yang dipilih?
                            
                            **Catatan:**
                            - Hanya siswa yang belum memiliki VA yang akan diproses
                            - Proses ini mungkin memakan waktu beberapa menit
                            - Pastikan koneksi internet stabil
                        ')
                        ->modalSubmitActionLabel('Ya, Generate Sekarang')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
