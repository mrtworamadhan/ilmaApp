<?php

namespace App\Policies;

use App\Models\DisbursementRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DisbursementRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Izinkan semua role yang relevan untuk MELIHAT daftar
        return $user->hasRole(['Admin Yayasan', 'Admin Sekolah', 'Kepala Bagian']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DisbursementRequest $disbursementRequest): bool
    {
        // Izinkan jika user adalah Admin Yayasan (bisa lihat semua)
        if ($user->hasRole('Admin Yayasan')) {
            return true;
        }

        // Izinkan jika user adalah pembuatnya (requester)
        if ($user->id === $disbursementRequest->requester_id) {
            return true;
        }
        
        // Izinkan jika user adalah Admin Sekolah dari sekolah yang sama
        if ($user->hasRole('Admin Sekolah') && $user->school_id === $disbursementRequest->school_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // ========================================================
        // REFACTOR DIMULAI DI SINI
        // ========================================================
        
        // HANYA Admin Sekolah dan Kepala Bagian yang bisa MENGAJUKAN
        return $user->hasRole(['Kepala Bagian']);

        // ========================================================
        // REFACTOR SELESAI
        // ========================================================
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DisbursementRequest $disbursementRequest): bool
    {
        // Hanya Admin Yayasan yang bisa menyetujui/mengubah status
        if ($user->hasRole('Admin Yayasan')) {
            return true;
        }

        // Izinkan pembuat untuk mengedit jika status masih PENDING
        return $user->id === $disbursementRequest->requester_id && $disbursementRequest->status === 'PENDING';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DisbursementRequest $disbursementRequest): bool
    {
        // Izinkan Admin Yayasan menghapus
        if ($user->hasRole('Admin Yayasan')) {
            return true;
        }
        
        // Izinkan pembuat menghapus jika status masih PENDING
        return $user->id === $disbursementRequest->requester_id && $disbursementRequest->status === 'PENDING';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DisbursementRequest $disbursementRequest): bool
    {
        return $user->hasRole('Admin Yayasan');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DisbursementRequest $disbursementRequest): bool
    {
        return $user->hasRole('Admin Yayasan');
    }
}