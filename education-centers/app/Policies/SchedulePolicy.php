<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
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
     * Voir la liste des séances.
     */
    public function viewAny(User $user): bool
    {
        return $user->isCenterAdmin();
    }

    /**
     * Voir le détail d'une séance.
     */
    public function view(User $user, Schedule $schedule): bool
    {
        return $user->isCenterAdmin() && $user->center_id === $schedule->center_id;
    }

    /**
     * Créer une séance.
     */
    public function create(User $user): bool
    {
        return $user->isCenterAdmin();
    }

    /**
     * Mettre à jour une séance.
     */
    public function update(User $user, Schedule $schedule): bool
    {
        return $user->isCenterAdmin() && $user->center_id === $schedule->center_id;
    }

    /**
     * Supprimer une séance.
     */
    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->isCenterAdmin() && $user->center_id === $schedule->center_id;
    }

    /**
     * Modifier le statut (annuler, terminer, etc.).
     */
    public function toggleStatus(User $user, Schedule $schedule): bool
    {
        return $user->isCenterAdmin() && $user->center_id === $schedule->center_id;
    }
}
