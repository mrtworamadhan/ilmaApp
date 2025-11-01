<?php

namespace App\Filament\Yayasan\Resources\DisbursementRequests\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DisbursementRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('budget_item_id')
                    ->label('Pilih Pos Anggaran')
                    ->relationship(
                        name: 'budgetItem',
                        titleAttribute: 'description',
                        // Tambahkan 'modifyQueryUsing' untuk memfilter
                        modifyQueryUsing: function (Builder $query) {
                            $user = Auth::user();
                            
                            // 1. Jika user tidak punya departemen (Admin Yayasan/Sekolah)
                            // Tampilkan semua pos anggaran yang SUDAH DISETUJUI
                            if (is_null($user->department_id)) {
                                return $query->whereHas('budget', function (Builder $q) use ($user) {
                                    $q->where('status', 'APPROVED');
                                    // Jika Admin Sekolah, filter lg by school_id
                                    if ($user->school_id) {
                                        $q->whereHas('department', fn(Builder $dq) => $dq->where('school_id', $user->school_id));
                                    }
                                });
                            }
                            
                            // 2. Jika user adalah Kepala Bagian (punya departemen)
                            // Tampilkan HANYA pos anggaran dari departemennya
                            // DAN yang status budget-nya sudah 'APPROVED'.
                            return $query->whereHas('budget', function (Builder $q) use ($user) {
                                $q->where('department_id', $user->department_id)
                                  ->where('status', 'APPROVED');
                            });
                        }
                    )
                    ->searchable()
                    ->preload() // <-- Tambahkan preload
                    ->required()
                    ->hiddenOn('edit'),
                TextInput::make('requested_amount')
                    ->label('Jumlah Diajukan')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->hiddenOn('edit'),

                // Nanti, Bendahara akan isi ini:
                FileUpload::make('realization_attachment')
                    ->label('Upload Nota / Laporan Realisasi')
                    ->directory('realisasi')
                    ->nullable()
                    ->visibleOn('edit'),
                TextInput::make('realization_amount')
                    ->label('Jumlah Realisasi (sesuai nota)')
                    ->numeric()
                    ->prefix('Rp')
                    ->nullable()
                    ->visibleOn('edit'),
            ]);
    }
}
