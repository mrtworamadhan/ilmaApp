<?php

namespace App\Filament\Yayasan\Resources\Users\Schemas;

use App\Models\Department;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Get; // <-- Ubah use statement ini
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role; // <-- Pastikan ini ada

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        // 1. Dapatkan user yang sedang login
        $loggedInUser = auth()->user();
            
        // 2. Tentukan apakah dia Admin Yayasan (true) atau Admin Sekolah (false)
        $isYayasanUser = $loggedInUser->school_id === null;

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
                
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->visibleOn('create'),
                
                Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable()
                    ->required()
                    ->live(),

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
                    ->live()
                    
                    ->default(fn () => $isYayasanUser ? null : $loggedInUser->school_id)
                    ->disabled(fn () => !$isYayasanUser) // Nonaktifkan jika BUKAN Admin Yayasan
                    
                    ->visible(function (\Filament\Schemas\Components\Utilities\Get $get) use ($isYayasanUser): bool {
                        if (!$isYayasanUser) return true; // Admin Sekolah WAJIB lihat (meski disabled)

                        $selectedRoleIds = $get('roles');
                        if (empty($selectedRoleIds)) return true;
                        
                        $roleNames = Role::whereIn('id', $selectedRoleIds)->pluck('name')->toArray();
                        
                        return empty(array_intersect(['admin_yayasan', 'bendahara_yayasan'], $roleNames));
                    })
                    ->required(function (\Filament\Schemas\Components\Utilities\Get $get) use ($isYayasanUser): bool {
                        if (!$isYayasanUser) return true;
                        $selectedRoleIds = $get('roles');
                        if (empty($selectedRoleIds)) return true;
                        $roleNames = Role::whereIn('id', $selectedRoleIds)->pluck('name')->toArray();
                        return empty(array_intersect(['admin_yayasan', 'bendahara_yayasan'], $roleNames));
                    }),

                Select::make('department_id')
                    ->label('Departemen / Bagian')
                    ->relationship(
                        name: 'department', // 1. Pakai nama relasi di Model User
                        titleAttribute: 'name', // 2. Tampilkan kolom 'name'
                        
                        // 3. Modifikasi query agar dependent & tenant-aware
                        modifyQueryUsing: function (Builder $query, \Filament\Schemas\Components\Utilities\Get $get) {
                            $schoolId = $get('school_id'); // Ambil school_id
                            $tenant = Filament::getTenant();

                            // Query ini akan dijalankan saat load 'options'
                            // DAN saat load 'saved value' di halaman edit
                            return $query->where('foundation_id', $tenant?->id)
                                          ->where('school_id', $schoolId);
                        }
                    )
                    ->searchable()
                    ->preload() // <-- Tambahkan ini untuk performa
                    ->nullable(),

                // HAPUS 'Hidden::make('school_id')' DARI SINI
            ]);
    }
}