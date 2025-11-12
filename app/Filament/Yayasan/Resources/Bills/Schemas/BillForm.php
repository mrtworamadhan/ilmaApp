<?php

namespace App\Filament\Yayasan\Resources\Bills\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class BillForm
{
    public static function configure(Schema $schema): Schema
    {
        $isYayasanUser = auth()->user()->school_id === null;
        $userSchoolId = auth()->user()->school_id;

        

        return $schema
            ->components([
                Hidden::make('school_id')
                    ->default(function () use ($isYayasanUser, $userSchoolId) {
                        // Untuk user sekolah, gunakan school_id user
                        if (!$isYayasanUser) {
                            return $userSchoolId;
                        }
                        return null; // Untuk yayasan, akan diisi via mutateDataBeforeCreate
                    })
                    ->afterStateUpdated(function ($state, $set, $get) {
                        // Otomatis update school_id ketika student dipilih
                        $studentId = $get('student_id');
                        if ($studentId) {
                            $student = \App\Models\Student::find($studentId);
                            if ($student && $student->school_id) {
                                $set('school_id', $student->school_id);
                            }
                        }
                    })
                    ->dehydrated(true), // Pastikan field ini disimpan

                Select::make('student_id')
                    ->label('Siswa')
                    ->relationship(
                        name: 'student',
                        titleAttribute: 'full_name',
                        modifyQueryUsing: fn (Builder $query) => 
                            $query
                                ->where('foundation_id', Filament::getTenant()->id)
                                // Jika user = Admin Sekolah, filter hanya siswa di sekolahnya
                                ->when($userSchoolId, fn ($q) => $q->where('school_id', $userSchoolId))
                    )
                    ->searchable(['full_name', 'nis']) 
                    ->preload()
                    ->required()
                    ->columnSpanFull(),

                // 2. Dropdown Kategori Biaya
                Select::make('fee_category_id')
                    ->label('Kategori Biaya')
                    ->relationship(
                        name: 'feeCategory',
                        titleAttribute: 'name',
                        // Filter kategori hanya milik yayasan ini
                        modifyQueryUsing: fn (Builder $query) => 
                            $query->where('foundation_id', Filament::getTenant()->id)
                    )
                    ->searchable()
                    ->preload()
                    ->required(),

                // 3. Nominal
                TextInput::make('amount')
                    ->label('Nominal (Rp)')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),

                // 4. Tanggal Jatuh Tempo
                DatePicker::make('due_date')
                    ->label('Jatuh Tempo')
                    ->required()
                    ->default(now()->addDays(10)), // Default 10 hari dari sekarang

                // 5. Status
                Select::make('status')
                    ->label('Status Tagihan')
                    ->options([ // Sesuai ERD
                        'unpaid' => 'Belum Lunas (Unpaid)',
                        'paid' => 'Lunas (Paid)',
                        'overdue' => 'Jatuh Tempo (Overdue)',
                        'cancelled' => 'Dibatalkan (Cancelled)',
                    ])
                    ->default('unpaid')
                    ->required(),
                
                // 6. Bulan (Opsional)
                TextInput::make('month')
                    ->label('Bulan Tagihan (Opsional)')
                    ->helperText('Isi jika ini tagihan bulanan. Format: YYYY-MM (Contoh: 2025-10)'),

                // 7. Keterangan
                Textarea::make('description')
                    ->label('Keterangan (Opsional)')
                    ->columnSpanFull(),
            ]);
    }
}
