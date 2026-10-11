<?php

namespace App\Policies;

use App\Models\Center;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CenterPolicy
{
    /**
     * Interception avant toute vérification.
     * Le Super Admin a tous les droits sur les centres.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the model.
     * Pour le moment, seul le super_admin a accès au CRUD /centers.
     * (Un center_admin pourra consulter son propre centre dans son dashboard dédié lors des étapes futures)
     */
    public function view(User $user, Center $center): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Center $center): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Center $center): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Center $center): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Center $center): bool
    {
        return $user->isSuperAdmin();
    }
}
