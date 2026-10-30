<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Interception avant toute vérification.
     * Le Super Admin a tous les droits sur les utilisateurs.
     */
    public function before(User $authUser, string $ability): ?bool
    {
        if ($authUser->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * viewAny : voir la liste des utilisateurs.
     * Super Admin → tous | Admin Centre → seulement son centre.
     * Teacher & Student → interdit.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->isSuperAdmin() || $authUser->isCenterAdmin();
    }

    /**
     * view : consulter un utilisateur spécifique.
     * Admin Centre ne peut voir que les utilisateurs de son centre.
     */
    public function view(User $authUser, User $user): bool
    {
        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $user->center_id;
        }

        return false;
    }

    /**
     * create : créer un utilisateur.
     * Exclusivité Super Admin : aucun Admin Centre ne peut créer d'utilisateur (ni prof, ni admin).
     */
    public function create(User $authUser): bool
    {
        return $authUser->isSuperAdmin();
    }

    /**
     * update : modifier un utilisateur.
     * Admin Centre : uniquement les utilisateurs de son propre centre.
     * Il ne peut pas modifier un super_admin ni déplacer vers un autre centre.
     */
    public function update(User $authUser, User $user): bool
    {
        if ($authUser->isCenterAdmin()) {
            // Ne peut pas modifier un super_admin
            if ($user->isSuperAdmin()) {
                return false;
            }

            // Ne peut modifier que les utilisateurs de son centre
            return $authUser->center_id === $user->center_id;
        }

        return false;
    }

    /**
     * delete : supprimer un utilisateur.
     * Super Admin uniquement (après contrôle métier).
     */
    public function delete(User $authUser, User $user): bool
    {
        if ($authUser->isSuperAdmin()) {
            // Ne peut pas supprimer le seul super_admin restant
            if ($user->isSuperAdmin()) {
                return User::where('role', User::ROLE_SUPER_ADMIN)->count() > 1;
            }

            return true;
        }

        return false;
    }

    /**
     * toggleStatus : désactiver/réactiver un utilisateur.
     * Super Admin → tous | Admin Centre → seulement son centre.
     */
    public function toggleStatus(User $authUser, User $user): bool
    {
        if ($authUser->isSuperAdmin()) {
            return true;
        }

        if ($authUser->isCenterAdmin()) {
            return $authUser->center_id === $user->center_id && ! $user->isSuperAdmin();
        }

        return false;
    }
}
