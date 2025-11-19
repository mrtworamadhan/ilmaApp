<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat Roles
        Role::firstOrCreate(['name' => 'Admin Yayasan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Admin Sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Kepala Bagian', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Kasir Kantin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Wali Kelas', 'guard_name' => 'web']);
    }
}