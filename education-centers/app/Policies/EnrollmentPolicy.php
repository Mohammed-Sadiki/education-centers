<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * Le Super Admin a tous les droits sur tous les centres.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Voir la liste des inscriptions.
     */
    public function viewAny(User $user): bool
    {
        return $user->isCenterAdmin();
    }

    /**
     * Consulter une inscription.
     */
    public function view(User $user, Enrollment $enrollment): bool
    {
        return $user->isCenterAdmin() && $user->center_id === $enrollment->center_id;
    }

    /**
     * Créer une inscription.
     */
    public function create(User $user): bool
    {
        return $user->isCenterAdmin();
    }

    /**
     * Mettre à jour une inscription.
     */
    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->isCenterAdmin() && $user->center_id === $enrollment->center_id;
    }

    /**
     * Supprimer une inscription.
     */
    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $user->isCenterAdmin() && $user->center_id === $enrollment->center_id;
    }

    /**
     * Modifier le statut d'une inscription (workflow).
     */
    public function changeStatus(User $user, Enrollment $enrollment): bool
    {
        return $user->isCenterAdmin() && $user->center_id === $enrollment->center_id;
    }
}
