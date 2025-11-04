<?php

namespace App\Filament\Traits;

use App\Models\Foundation;
use Filament\Facades\Filament;

trait HasModuleAccess
{
    protected static function getRequiredModule(): string
    {
        if (property_exists(static::class, 'requiredModule')) {
            return static::$requiredModule;
        }
        
        return 'default';
    }

    /**
     * Fungsi helper utama untuk cek modul
     */
    protected static function checkModuleAccess(): bool
    {
        $module = static::getRequiredModule();

        if ($module === 'default') {
            return true;
        }

        $tenant = Filament::getTenant();
        return $tenant instanceof Foundation && $tenant->hasModule($module);
    }

    /**
     * Untuk Halaman (Otomatis dipanggil Filament)
     * Cek apakah halaman ini boleh diakses.
     */
    public static function canAccess(): bool
    {
        return static::checkModuleAccess();
    }

    /**
     * Untuk Resource (Otomatis dipanggil Filament)
     * Cek apakah resource ini boleh dilihat di navigasi.
     */
    public static function canViewAny(): bool
    {
        return static::checkModuleAccess();
    }

    /**
     * Versi gabungan dengan Role (bisa dipanggil manual jika perlu)
     */
    public static function canAccessWithRolesAndModule(array $roles): bool
    {
        $hasRole = auth()->check() && auth()->user()->hasRole($roles);
        if (!$hasRole) {
            return false;
        }
        
        return static::checkModuleAccess();
    }
}