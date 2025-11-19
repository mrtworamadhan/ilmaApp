<?php

namespace App\Filament\Yayasan\Resources\Bills\Schemas;

use App\Models\FeeCategory;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                Textarea::make('description')
                    ->label('Keterangan (Judul Tagihan)')
                    ->helperText('Misal: "Tagihan Manual - Buku Paket Kelas 1"')
                    ->required()
                    ->columnSpan(2),
                
                DatePicker::make('due_date')
                    ->label('Jatuh Tempo')
                    ->required()
                    ->default(now()->addDays(10)),

                Select::make('status')
                    ->label('Status Tagihan')
                    ->options([
                        'unpaid' => 'Belum Lunas (Unpaid)',
                        'paid' => 'Lunas (Paid)',
                        'overdue' => 'Jatuh Tempo (Overdue)',
                        'cancelled' => 'Dibatalkan (Cancelled)',
                    ])
                    ->default('unpaid')
                    ->required(),
                
                TextInput::make('month')
                    ->label('Bulan Tagihan (Opsional)')
                    ->helperText('Format: YYYY-MM (Contoh: 2025-10)'),

                // ===================================================
                // PENGGANTI 'fee_category_id' dan 'amount'
                // ===================================================
                Repeater::make('items') // <-- Nama relasi 'items()'
                    ->label('Rincian Item Tagihan')
                    ->relationship() // <-- Menghubungkan ke relasi BillItem
                    ->schema([
                        Select::make('fee_category_id')
                            ->label('Kategori Biaya')
                            ->options(
                                FeeCategory::where('foundation_id', Filament::getTenant()->id)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            // Otomatis isi 'description' saat dipilih
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                $feeCategory = FeeCategory::find(id: $state);
                                if ($feeCategory) {
                                    $set('description', $feeCategory->name);
                                }
                            }),

                        TextInput::make('description')
                            ->label('Deskripsi Item')
                            ->required(),
                        
                        TextInput::make('amount')
                            ->label('Nominal (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->live(onBlur: true), // <-- 'live' untuk hitung total
                    ])
                    ->columns(3)
                    ->addActionLabel('Tambah Rincian')
                    ->defaultItems(1) // Minimal 1 rincian
                    ->live() // <-- 'live' agar Repeater menghitung
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        // OTOMATIS HITUNG TOTAL
                        $items = $get('items') ?? [];
                        $total = collect($items)->sum(fn($item) => (float)$item['amount']);
                        $set('total_amount', $total);
                    })
                    ->columnSpanFull(),

                // Field 'total_amount' (Baru)
                TextInput::make('total_amount')
                    ->label('Total Tagihan (Otomatis)')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled() // Dikunci (read-only)
                    ->dehydrated() // Paksa kirim ke database
                    ->required(),
            ])
            ->columns(2);
    }
}
