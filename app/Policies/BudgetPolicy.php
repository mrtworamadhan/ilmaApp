<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BudgetPolicy
{
    use HandlesAuthorization;

    /**
     * Izinkan Admin Yayasan melihat semua.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('Admin Yayasan')) {
            return true;
        }
        return null;
    }

    /**
     * User bisa melihat daftar budget jika dia Admin Sekolah
     * atau jika dia Kepala Bagian (hanya miliknya).
     * (Kita akan terapkan filter 'hanya miliknya' di Resource, ini hanya untuk menu)
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Admin Sekolah', 'Kepala Bagian']);
    }

    /**
     * User bisa melihat detail budget.
     */
    public function view(User $user, Budget $budget): bool
    {
        // Admin Sekolah bisa lihat semua budget di sekolahnya
        if ($user->hasRole('Admin Sekolah')) {
            return $user->school_id === $budget->department->school_id;
        }

        // Kepala Bagian hanya bisa lihat budget departemennya
        if ($user->hasRole('Kepala Bagian')) {
            return $user->department_id === $budget->department_id;
        }

        return false;
    }

    /**
     * User bisa membuat budget jika dia Kepala Bagian atau Admin Sekolah.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Admin Sekolah', 'Kepala Bagian']);
    }

    /**
     * User bisa mengedit budget JIKA statusnya masih DRAFT.
     */
    public function update(User $user, Budget $budget): bool
    {
        if ($budget->status !== 'PENDING') {
            return false; // Jika sudah di-submit, tidak bisa diedit
        }

        if ($user->hasRole('Admin Sekolah')) {
            return $user->school_id === $budget->department->school_id;
        }

        if ($user->hasRole('Kepala Bagian')) {
            return $user->department_id === $budget->department_id;
        }

        return false;
    }

    /**
     * Hanya Admin Sekolah yang bisa menghapus (jika masih DRAFT)
     */
    public function delete(User $user, Budget $budget): bool
    {
        return $user->hasRole('Admin Sekolah') && $budget->status === 'PENDING';
    }

}