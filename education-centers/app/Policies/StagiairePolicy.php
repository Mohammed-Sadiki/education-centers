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
     *
     * Super Admin : tous les centres autorisés. Admin Centre : son centre
     * uniquement — le périmètre est appliqué par le contrôleur.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->isCenterAdmin();
    }

    /**
     * Consulter un dossier stagiaire : uniquement un dossier de son centre.
     */
    public function view(User $authUser, Stagiaire $stagiaire): bool
    {
        return $authUser->isCenterAdmin()
            && $authUser->center_id !== null
            && (int) $authUser->center_id === (int) $stagiaire->center_id;
    }

    /**
     * Créer un dossier stagiaire. Le centre d'un Admin Centre est imposé par
     * le serveur (prepareForValidation + contrôleur) : il ne peut donc inscrire
     * que des stagiaires de son propre centre.
     */
    public function create(User $authUser): bool
    {
        return $authUser->isCenterAdmin() && $authUser->center_id !== null;
    }

    /**
     * Modifier un dossier stagiaire de son centre.
     */
    public function update(User $authUser, Stagiaire $stagiaire): bool
    {
        return $this->view($authUser, $stagiaire);
    }

    /**
     * Supprimer un dossier stagiaire : opération réservée au Super Admin.
     */
    public function delete(User $authUser, Stagiaire $stagiaire): bool
    {
        return false;
    }
}
