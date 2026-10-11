<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    /**
     * Interception avant toute vérification.
     * Le Super Admin a tous les droits sur toutes les salles de tous les centres.
     */
    public function before(User $authUser, string $ability): ?bool
    {
        if ($authUser->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * viewAny : voir la liste des salles.
     * Accessible au Super Admin et à l'Admin Centre.
     * Refusé pour Professeur et Élève (403).
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->isCenterAdmin();
    }

    /**
     * view : consulter une salle spécifique.
     * Admin Centre : uniquement les salles de son propre centre.
     */
    public function view(User $authUser, Room $room): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $room->center_id;
        }

        return false;
    }

    /**
     * create : créer une salle.
     * Super Admin (géré par before) et Admin Centre.
     */
    public function create(User $authUser): bool
    {
        return $authUser->isCenterAdmin();
    }

    /**
     * update : modifier une salle.
     * Admin Centre : uniquement les salles de son propre centre.
     */
    public function update(User $authUser, Room $room): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $room->center_id;
        }

        return false;
    }

    /**
     * toggleStatus : activer / désactiver une salle.
     */
    public function toggleStatus(User $authUser, Room $room): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $room->center_id;
        }

        return false;
    }

    /**
     * delete : supprimer une salle.
     * Admin Centre : uniquement dans son propre centre.
     */
    public function delete(User $authUser, Room $room): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $room->center_id;
        }

        return false;
    }
}
