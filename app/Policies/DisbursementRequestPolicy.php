<?php

namespace App\Policies;

use App\Models\DisbursementRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DisbursementRequestPolicy
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
     * Siapa yang bisa lihat menu Pengajuan Pencairan.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Admin Sekolah', 'Kepala Bagian']);
    }

    /**
     * User bisa melihat detail pengajuan.
     */
    public function view(User $user, DisbursementRequest $disbursementRequest): bool
    {
        if ($user->hasRole('Admin Sekolah')) {
            // Pastikan admin sekolah & pengajuan ada di sekolah yg sama
            return $user->school_id === $disbursementRequest->budgetItem->budget->department->school_id;
        }

        if ($user->hasRole('Kepala Bagian')) {
            // Pastikan ini adalah pengajuan miliknya
            return $user->id === $disbursementRequest->requester_id;
        }

        return false;
    }

    /**
     * Hanya Kepala Bagian yang bisa MEMBUAT pengajuan.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Kepala Bagian');
    }

    /**
     * User bisa mengedit pengajuan JIKA statusnya masih PENDING.
     */
    public function update(User $user, DisbursementRequest $disbursementRequest): bool
    {
        // Hanya bisa edit jika masih PENDING dan dia adalah pembuatnya
        return $disbursementRequest->status === 'PENDING' && $user->id === $disbursementRequest->requester_id;
    }

    /**
     * Hanya Admin Sekolah yang bisa menghapus (jika masih PENDING)
     */
    public function delete(User $user, DisbursementRequest $disbursementRequest): bool
    {
        return $user->hasRole('Admin Sekolah') && $disbursementRequest->status === 'PENDING';
    }
}