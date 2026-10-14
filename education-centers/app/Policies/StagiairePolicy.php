<?php

namespace App\Policies;

use App\Models\Stagiaire;
use App\Models\User;

class StagiairePolicy
{
    /**
     * Interception avant toute vérification.
     * Le Super Admin a tous les droits sur les dossiers stagiaires.
     */
    public function before(User $authUser, string $ability): ?bool
    {
        if ($authUser->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Voir la liste des stagiaires.
     * Seul Super Admin y a accès pour le moment.
     */
    public function viewAny(User $authUser): bool
    {
        return false;
    }

    /**
     * Consulter un dossier stagiaire.
     */
    public function view(User $authUser, Stagiaire $stagiaire): bool
    {
        return false;
    }

    /**
     * Créer un dossier stagiaire.
     */
    public function create(User $authUser): bool
    {
        return false;
    }

    /**
     * Modifier un dossier stagiaire.
     */
    public function update(User $authUser, Stagiaire $stagiaire): bool
    {
        return false;
    }

    /**
     * Supprimer un dossier stagiaire.
     */
    public function delete(User $authUser, Stagiaire $stagiaire): bool
    {
        return false;
    }
}
