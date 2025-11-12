<?php

namespace App\Filament\Yayasan\Resources\Schools\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TimePicker;
use Illuminate\Support\Str;

class SchoolForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Sekolah')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Sekolah')
                            ->required(),
                        
                        Select::make('level')
                            ->label('Jenjang')
                            ->options([
                                // Sesuai ERD 
                                'tk' => 'TK / PAUD',
                                'sd' => 'SD / MI',
                                'smp' => 'SMP / MTS',
                                'sma' => 'SMA / MA',
                                'pondok' => 'Pondok Pesantren',
                            ])
                            ->required(),

                        TextInput::make('headmaster')
                            ->label('Nama Kepala Sekolah'),
                        
                        TextInput::make('phone')
                            ->label('Telepon Sekolah'),

                        Textarea::make('address')
                            ->label('Alamat')
                            ->columnSpanFull(),
                    ])->columns(2), // Kita buat 2 kolom agar rapi

                // V-- INI DIA BLOK BARUNYA --V
                Section::make('Pengaturan Absensi Guru')
                    ->description('Atur jam masuk dan pulang standar untuk guru di sekolah ini.')
                    ->schema([
                        TimePicker::make('teacher_check_in_time')
                            ->label('Jam Masuk Guru')
                            ->default('07:00:00')
                            ->seconds(false) // Kita tidak perlu detik
                            ->required(),
                            
                        TimePicker::make('teacher_check_out_time')
                            ->label('Jam Pulang Guru')
                            ->default('14:00:00')
                            ->seconds(false) // Kita tidak perlu detik
                            ->required(),
                    ])->columns(2),
                Section::make('API & Keamanan')
                    ->description('Pengaturan API Key untuk integrasi hardware (scanner RFID).')
                    ->schema([
                        TextInput::make('api_key')
                            ->label('API Key (Untuk Scanner RFID)')
                            ->readOnly() // Tidak bisa diketik manual
                            ->copyable() // Tambah tombol "Copy"
                            ->helperText('Klik "Generate" untuk membuat key baru. Berikan key ini ke vendor scanner RFID.')
                            ->columnSpanFull()
                            ->suffixAction( // Ini adalah tombol di dalam input
                                Action::make('generateApiKey')
                                    ->label('Generate')
                                    ->icon('heroicon-o-key')
                                    ->action(function (callable $set) {
                                        // 1. Buat string acak 32 karakter
                                        $apiKey = Str::random(32); 
                                        // 2. Set nilai field 'api_key' dengan string baru
                                        $set('api_key', $apiKey); 
                                    })
                            )
                    ])
            ]);
    }
}
