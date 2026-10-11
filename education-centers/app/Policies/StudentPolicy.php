<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    /**
     * Interception avant toute vérification.
     * Le Super Admin a tous les droits sur les dossiers étudiants.
     */
    public function before(User $authUser, string $ability): ?bool
    {
        if ($authUser->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * viewAny : voir la liste des dossiers étudiants.
     * Seuls Super Admin et Admin Centre y ont accès.
     * Professeur et Élève : 403.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->isCenterAdmin();
    }

    /**
     * view : consulter un dossier étudiant.
     * Admin Centre : uniquement les étudiants de son propre centre.
     */
    public function view(User $authUser, Student $student): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $student->center_id;
        }

        return false;
    }

    /**
     * create : créer un dossier étudiant.
     * Super Admin (handled by before) et Admin Centre.
     */
    public function create(User $authUser): bool
    {
        return $authUser->isCenterAdmin();
    }

    /**
     * update : modifier un dossier étudiant.
     * Admin Centre : uniquement les étudiants de son propre centre.
     */
    public function update(User $authUser, Student $student): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $student->center_id;
        }

        return false;
    }

    /**
     * toggleStatus : activer / désactiver un dossier étudiant.
     */
    public function toggleStatus(User $authUser, Student $student): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $student->center_id;
        }

        return false;
    }

    /**
     * delete : supprimer un dossier étudiant.
     */
    public function delete(User $authUser, Student $student): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $student->center_id;
        }

        return false;
    }
}
