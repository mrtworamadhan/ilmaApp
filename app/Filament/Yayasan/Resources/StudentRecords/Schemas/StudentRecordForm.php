<?php

namespace App\Filament\Yayasan\Resources\StudentRecords\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class StudentRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Catatan')
                    ->columns(2)
                    ->schema([
                        Select::make('student_id')
                            ->label('Siswa')
                            ->relationship('student', 'full_name', function (Builder $query) {
                                // Ambil school_id dari user (Wali Kelas/Kesiswaan)
                                $userSchoolId = auth()->user()->school_id;
                                
                                // Hanya tampilkan siswa dari sekolah user yang login
                                if ($userSchoolId) {
                                    return $query->where('school_id', $userSchoolId);
                                }
                                // Jika Admin Yayasan (meski dia tidak bisa create),
                                // tunjukkan semua (atau batasi lebih lanjut jika perlu)
                                return $query; 
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                        
                        Select::make('type')
                            ->label('Tipe Catatan')
                            ->options([
                                'pelanggaran' => 'Pelanggaran',
                                'prestasi' => 'Prestasi',
                                'perizinan' => 'Perizinan',
                                'catatan_bk' => 'Catatan BK',
                            ])
                            ->required(),
                        
                        DatePicker::make('date')
                            ->label('Tanggal Kejadian')
                            ->required()
                            ->default(now()),

                        TextInput::make('points')
                            ->label('Poin')
                            ->numeric()
                            ->default(0)
                            ->helperText('Isi poin positif (misal: 10) untuk prestasi, atau poin negatif (misal: -5) untuk pelanggaran.'),
                        
                        RichEditor::make('description')
                            ->label('Deskripsi / Keterangan')
                            ->required()
                            ->columnSpanFull(),
                    ]),
                    
            ]);
    }
}
