<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    /**
     * Interception avant toute vérification.
     * Le Super Admin a tous les droits sur les dossiers professeurs.
     */
    public function before(User $authUser, string $ability): ?bool
    {
        if ($authUser->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * viewAny : voir la liste des dossiers professeurs.
     * Seuls Super Admin et Admin Centre y ont accès.
     * Professeur et Élève : 403.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->isCenterAdmin();
    }

    /**
     * view : consulter un dossier professeur.
     * Admin Centre : uniquement les professeurs de son propre centre.
     */
    public function view(User $authUser, Teacher $teacher): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $teacher->center_id;
        }

        return false;
    }

    /**
     * create : créer un dossier professeur.
     * Exclusivité Super Admin : aucun Admin Centre ne peut créer de dossier professeur.
     */
    public function create(User $authUser): bool
    {
        return false;
    }

    /**
     * update : modifier un dossier professeur.
     * Admin Centre : uniquement les professeurs de son propre centre.
     */
    public function update(User $authUser, Teacher $teacher): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $teacher->center_id;
        }

        return false;
    }

    /**
     * toggleStatus : activer / désactiver un dossier professeur.
     */
    public function toggleStatus(User $authUser, Teacher $teacher): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $teacher->center_id;
        }

        return false;
    }

    /**
     * delete : supprimer un dossier professeur.
     */
    public function delete(User $authUser, Teacher $teacher): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $teacher->center_id;
        }

        return false;
    }
}
