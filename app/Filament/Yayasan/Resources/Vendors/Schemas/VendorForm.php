<?php

namespace App\Filament\Yayasan\Resources\Vendors\Schemas;

use App\Models\School;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Vendor')
                    ->description('Data stan/kantin yang akan didaftarkan.')
                    ->schema([
                        // Dropdown untuk memilih sekolah (otomatis difilter by foundation)
                        Select::make('school_id')
                            ->label('Sekolah')
                            ->options(function () {
                                // Hanya tampilkan sekolah milik yayasan admin
                                return School::where('foundation_id', Auth::user()->foundation_id)
                                    ->pluck('name', 'id');
                            })
                            ->required(),
                        TextInput::make('name')
                            ->label('Nama Vendor/Kantin')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                        Select::make('status')
                            ->options([
                                'active' => 'Aktif',
                                'inactive' => 'Tidak Aktif',
                            ])
                            ->required()
                            ->default('active'),
                    ])->columns(2),

                Section::make('Akun Kasir')
                    ->description('Buat akun login untuk kasir vendor ini.')
                    ->schema([
                        TextInput::make('user_name') // Kita pakai nama fiktif
                            ->label('Nama Kasir')
                            ->mapped(false) // Tidak disimpan di tabel vendors
                            ->required(),
                        TextInput::make('user_email') // Nama fiktif
                            ->label('Email Kasir')
                            ->email()
                            ->unique(table: 'users', column: 'email') // Validasi unique di tabel users
                            ->mapped(false) // Tidak disimpan di tabel vendors
                            ->required(),
                        TextInput::make('user_password') // Nama fiktif
                            ->label('Password Kasir')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->mapped(false) // Tidak disimpan di tabel vendors
                            ->dehydrated(fn (string $context): bool => $context === 'create'), // Hanya wajib saat create
                    ])->columns(2),
            ]);
    }
}
