<?php

namespace App\Policies;

use App\Models\ClassRoom;
use App\Models\User;

class ClassPolicy
{
    /**
     * Interception avant toute vérification.
     * Le Super Admin a tous les droits sur toutes les classes de tous les centres.
     */
    public function before(User $authUser, string $ability): ?bool
    {
        if ($authUser->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * viewAny : voir la liste des classes.
     * Accessible au Super Admin et à l'Admin Centre.
     * Refusé pour Professeur et Élève (403).
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->isCenterAdmin();
    }

    /**
     * view : consulter une classe spécifique.
     * Admin Centre : uniquement les classes de son propre centre.
     */
    public function view(User $authUser, ClassRoom $classRoom): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $classRoom->center_id;
        }

        return false;
    }

    /**
     * create : créer une classe.
     * Super Admin (géré par before) et Admin Centre.
     */
    public function create(User $authUser): bool
    {
        return $authUser->isCenterAdmin();
    }

    /**
     * update : modifier une classe.
     * Admin Centre : uniquement les classes de son propre centre.
     */
    public function update(User $authUser, ClassRoom $classRoom): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $classRoom->center_id;
        }

        return false;
    }

    /**
     * toggleStatus : activer / désactiver une classe.
     */
    public function toggleStatus(User $authUser, ClassRoom $classRoom): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $classRoom->center_id;
        }

        return false;
    }

    /**
     * delete : supprimer une classe.
     * Admin Centre : uniquement dans son propre centre.
     */
    public function delete(User $authUser, ClassRoom $classRoom): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $classRoom->center_id;
        }

        return false;
    }

    /**
     * assignTeacher : affecter un professeur à une classe.
     */
    public function assignTeacher(User $authUser, ClassRoom $classRoom): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $classRoom->center_id;
        }

        return false;
    }

    /**
     * unassignTeacher : retirer un professeur d'une classe.
     */
    public function unassignTeacher(User $authUser, ClassRoom $classRoom): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $classRoom->center_id;
        }

        return false;
    }
}

