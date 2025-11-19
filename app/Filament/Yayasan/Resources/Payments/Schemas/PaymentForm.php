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
use Illuminate\Support\Number;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        $foundationId = Filament::getTenant()->id;
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
                                ->when($userSchoolId, fn ($q) => $q->where('school_id', $userSchoolId))
                    )
                    ->searchable(['name', 'nis'])
                    ->preload()
                    ->live()
                    ->required(),

                Select::make('bill_id')
                    ->label('Tagihan yang Dibayar')
                    ->required()
                    ->options(function (Get $get) {
                        $studentId = $get('student_id');
                        if (!$studentId) {
                            return [];
                        }
                        
                        // 5. Ambil tagihan gabungan yg belum lunas
                        $bills = Bill::where('student_id', $studentId)
                            ->whereIn('status', ['unpaid', 'overdue']) // <-- Pakai In
                            ->get();
                        
                        // 6. Format label baru
                        return $bills->mapWithKeys(function ($bill) {
                            $label = sprintf(
                                "%s (Total: %s)",
                                $bill->description, // <-- Ambil 'description' baru
                                Number::currency($bill->total_amount, 'IDR') // <-- Ambil 'total_amount' baru
                            );
                            
                            return [$bill->id => $label];
                        })->toArray();
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        // 7. Auto-fill nominal dari 'total_amount'
                        if ($state) {
                            $bill = Bill::find($state);
                            if ($bill) {
                                $set('amount_paid', $bill->total_amount); // <-- PERBAIKAN
                            }
                        }
                    })
                    ->helperText('Hanya tagihan yang "Belum Lunas" akan muncul di sini'),

                // Field 'amount_paid' (Logika Anda sudah benar)
                TextInput::make('amount_paid')
                    ->label('Nominal Pembayaran (Rp)')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    // 8. Kita biarkan bisa diedit untuk bayar parsial
                    ->helperText('Otomatis terisi, tapi bisa diubah jika bayar parsial.'),

                // Sisa field (Logika Anda sudah benar)
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

                Hidden::make('status')->default('success'),
                Hidden::make('payment_for')->default('bill'),
            ])
            ->columns(2);
    }
}