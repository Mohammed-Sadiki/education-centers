<?php

namespace App\Policies;

use App\Models\Formation;
use App\Models\User;

class FormationPolicy
{
    /**
     * Interception avant toute vérification.
     * Le Super Admin a tous les droits sur les formations de tous les centres.
     */
    public function before(User $authUser, string $ability): ?bool
    {
        if ($authUser->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * viewAny : voir la liste des formations.
     * Accessible au Super Admin et à l'Admin Centre.
     * Refusé pour Professeur et Élève (403).
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->isCenterAdmin();
    }

    /**
     * view : consulter une formation spécifique.
     * Admin Centre : uniquement les formations de son propre centre.
     */
    public function view(User $authUser, Formation $formation): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $formation->center_id;
        }

        return false;
    }

    /**
     * create : créer une formation.
     * Super Admin (géré par before) et Admin Centre.
     */
    public function create(User $authUser): bool
    {
        return $authUser->isCenterAdmin();
    }

    /**
     * update : modifier une formation.
     * Admin Centre : uniquement les formations de son propre centre.
     */
    public function update(User $authUser, Formation $formation): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $formation->center_id;
        }

        return false;
    }

    /**
     * toggleStatus : activer / désactiver une formation.
     */
    public function toggleStatus(User $authUser, Formation $formation): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $formation->center_id;
        }

        return false;
    }

    /**
     * delete : supprimer une formation.
     * Admin Centre : uniquement dans son propre centre.
     */
    public function delete(User $authUser, Formation $formation): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $formation->center_id;
        }

        return false;
    }
}
