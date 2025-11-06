<?php

namespace App\Filament\Yayasan\Resources\AdmissionRegistrations\Tables;

use App\Models\School;
use App\Models\AdmissionRegistration;
use App\Models\Student;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdmissionRegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('registration_number')
                    ->label('No. Daftar')
                    ->searchable(),
                TextColumn::make('full_name')
                    ->label('Nama Calon Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('school.name')
                    ->label('Sekolah')
                    ->sortable()
                    ->visible(fn () => auth()->user()->school_id === null), // Hanya Admin Yayasan
                TextColumn::make('parent_name')
                    ->label('Nama Orang Tua')
                    ->searchable(),
                TextColumn::make('parent_phone')
                    ->label('No. HP'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'baru' => 'gray',
                        'diverifikasi' => 'info',
                        'seleksi' => 'warning',
                        'diterima' => 'success',
                        'ditolak' => 'danger',
                        'menjadi_siswa' => 'primary',
                    })
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('Sekolah')
                    ->options(fn () => School::where('foundation_id', Filament::getTenant()->id)->pluck('name', 'id'))
                    ->visible(fn () => auth()->user()->school_id === null),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'baru' => 'Baru',
                        'diverifikasi' => 'Terverifikasi (Bayar)',
                        'seleksi' => 'Proses Seleksi',
                        'diterima' => 'Diterima',
                        'ditolak' => 'Ditolak',
                        'menjadi_siswa' => 'Sudah Menjadi Siswa',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Action::make('Verifikasi')
                    ->label('Verifikasi Bayar')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(fn (AdmissionRegistration $record) => $record->update(['status' => 'seleksi']))
                    // GABUNGKAN LOGIKA PERAN DAN STATUS DI SINI:
                    ->visible(function (AdmissionRegistration $record) {
                        return $record->status === 'diverifikasi' &&
                               auth()->user()->hasAnyRole(['Admin Yayasan', 'Staf Kesiswaan']);
                    }),

                Action::make('Seleksi')
                    ->label('Proses Seleksi')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(function (AdmissionRegistration $record) {
                        return ($record->status === 'seleksi' || $record->status === 'diverifikasi') &&
                               auth()->user()->hasAnyRole(['Admin Yayasan', 'Staf Kesiswaan']);
                    })
                    ->action(fn (AdmissionRegistration $record) => $record->update(['status' => 'seleksi'])),

                Action::make('Terima')
                    ->label('Terima Siswa')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (AdmissionRegistration $record) => $record->update(['status' => 'diterima']))
                    // GABUNGKAN LOGIKA PERAN DAN STATUS DI SINI:
                    ->visible(function (AdmissionRegistration $record) {
                        return $record->status === 'seleksi' &&
                               auth()->user()->hasAnyRole(['Admin Yayasan', 'Staf Kesiswaan']);
                    }),

                Action::make('Tolak')
                    ->label('Tolak Siswa')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (AdmissionRegistration $record) => $record->update(['status' => 'ditolak']))
                    // GABUNGKAN LOGIKA PERAN DAN STATUS DI SINI:
                    ->visible(function (AdmissionRegistration $record) {
                        return ($record->status === 'seleksi' || $record->status === 'diverifikasi') &&
                               auth()->user()->hasAnyRole(['Admin Yayasan', 'Staf Kesiswaan']);
                    }),

                Action::make('JadikanSiswa')
                    ->label('Jadikan Siswa Aktif')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Jadikan Siswa Aktif?')
                    ->modalDescription('Aksi ini akan membuat data siswa baru di Data Master Siswa. Aksi ini tidak bisa dibatalkan.')
                    ->visible(fn (AdmissionRegistration $record) => $record->status === 'diterima')
                    ->action(function (AdmissionRegistration $record) {
                        try {
                            // 1. Buat Siswa baru dari data pendaftaran
                            Student::create([
                                'foundation_id' => $record->foundation_id,
                                'school_id' => $record->school_id,
                                'class_id' => null, // Admin TU harus update kelas nanti
                                'parent_id' => null, // Admin TU harus link ke user ortu nanti
                                'nis' => null, // Admin TU harus input NIS
                                'nisn' => null, // Admin TU bisa input
                                'full_name' => $record->full_name,
                                'gender' => $record->gender,
                                'birth_place' => $record->birth_place,
                                'birth_date' => $record->birth_date,
                                'religion' => $record->religion,
                                'father_name' => $record->parent_name, // Asumsi
                                'phone' => $record->parent_phone,
                                'status' => 'active', // Langsung aktif
                            ]);

                            // 2. Update status pendaftaran
                            $record->update(['status' => 'menjadi_siswa']);

                            Notification::make()
                                ->title('Siswa Berhasil Dibuat')
                                ->body("Siswa '{$record->full_name}' telah berhasil dibuat.")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Membuat Siswa')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(function (AdmissionRegistration $record) {
                        return $record->status === 'diterima' &&
                               auth()->user()->hasAnyRole(['Admin Yayasan', 'Staf Keuangan / TU']);
                    }),
                    
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
