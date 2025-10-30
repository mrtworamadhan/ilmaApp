<?php

namespace App\Filament\Yayasan\Resources\Payments\Schemas;

use App\Models\Bill;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class PaymentForm
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
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => 
                            $query
                                ->where('foundation_id', Filament::getTenant()->id)
                                ->when($userSchoolId, fn ($q) => $q->where('school_id', $userSchoolId))
                    )
                    ->searchable(['name', 'nis'])
                    ->preload()
                    ->live()
                    ->required(),

                // 2. Dropdown Tagihan (Reaktif) - FIXED
                Select::make('bill_id')
                    ->label('Tagihan yang Dibayar')
                    ->required()
                    ->options(function (Get $get) {
                        $studentId = $get('student_id');
                        if (!$studentId) {
                            return [];
                        }
                        
                        // Ambil tagihan HANYA dari siswa yg dipilih dan yang statusnya 'unpaid'
                        $bills = Bill::where('student_id', $studentId)
                                   ->where('status', 'unpaid')
                                   ->get();
                        
                        // Format options dengan label yang tidak mungkin null
                        return $bills->mapWithKeys(function ($bill) {
                            // Buat label yang informatif dan tidak akan null
                            $label = 'Tagihan #' . $bill->id;
                            
                            if ($bill->feeCategory) {
                                $label .= ' - ' . $bill->feeCategory->name;
                            }
                            
                            $label .= ' - Rp ' . number_format($bill->amount);
                            
                            if ($bill->due_date) {
                                $label .= ' (Jatuh Tempo: ' . $bill->due_date->format('d/m/Y') . ')';
                            }
                            
                            return [$bill->id => $label];
                        })->toArray();
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        // Auto-fill nominal
                        if ($state) {
                            $bill = Bill::find($state);
                            if ($bill) {
                                $set('amount_paid', $bill->amount);
                            }
                        }
                    })
                    ->helperText('Hanya tagihan yang "Belum Lunas" akan muncul di sini'),

                // 3. Nominal (Otomatis terisi)
                TextInput::make('amount_paid')
                    ->label('Nominal Pembayaran (Rp)')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),

                // 4. Metode Bayar
                Select::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => 'Tunai (Cash)',
                        'va' => 'Manual Transfer (VA)',
                    ])
                    ->default('cash')
                    ->required(),
                
                DatePicker::make('paid_at')
                    ->label('Tanggal Bayar')
                    ->default(now())
                    ->required(),

                Textarea::make('description')
                    ->label('Catatan (Opsional)')
                    ->columnSpanFull(),

                // Sembunyikan field ini, akan diisi otomatis
                Hidden::make('status')->default('success'),
                Hidden::make('payment_for')->default('bill'),
            ]);
    }
}