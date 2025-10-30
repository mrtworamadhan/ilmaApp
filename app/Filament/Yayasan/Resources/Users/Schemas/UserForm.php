<?php

namespace App\Filament\Yayasan\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $isYayasanUser = auth()->user()->school_id === null;

        $roles = [
            'admin_sekolah' => 'Admin Sekolah',
            'bendahara_sekolah' => 'Bendahara Sekolah',
            'guru' => 'Guru',
            'orangtua' => 'Orang Tua',
        ];

        if ($isYayasanUser) {
            $roles['admin_yayasan'] = 'Admin Yayasan';
            $roles['bendahara_yayasan'] = 'Bendahara Yayasan';
        }
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required(),

                TextInput::make('email')
                    ->label('Email (untuk login)')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                
                // Field password hanya muncul saat 'Create'
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state)) // Hash password
                    ->visibleOn('create'), // Hanya tampil saat buat baru
                
                Select::make('role')
                    ->label('Role / Hak Akses')
                    ->options($roles) // Gunakan daftar role dinamis
                    ->required()
                    ->reactive(), // <-- Penting agar field school_id bereaksi

                // --- LOGIKA MULTI-LEVEL UNTUK SEKOLAH ---

                // 1. Dropdown Sekolah (Hanya untuk Admin Yayasan)
                Select::make('school_id')
                    ->label('Ditugaskan di Sekolah')
                    ->relationship(
                        name: 'school',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => 
                            $query->where('foundation_id', Filament::getTenant()->id)
                    )
                    ->searchable()
                    ->preload()
                    // Tampil HANYA jika:
                    // 1. User = Admin Yayasan
                    // 2. Role yang dipilih BUKAN role level yayasan
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => 
                        $isYayasanUser && 
                        !in_array($get('role'), ['admin_yayasan', 'bendahara_yayasan'])
                    )
                    // Wajib diisi JIKA terlihat
                    ->required(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => 
                        $isYayasanUser && 
                        !in_array($get('role'), ['admin_yayasan', 'bendahara_yayasan'])
                    ),

                // 2. Field Tersembunyi (Hanya untuk Admin Sekolah)
                Hidden::make('school_id')
                    ->default(auth()->user()->school_id)
                    ->hidden($isYayasanUser), // Sembunyikan jika Admin Yayasan
            ]);
    }
}
