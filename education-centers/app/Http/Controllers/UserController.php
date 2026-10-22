<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Center;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs avec recherche, filtres et pagination.
     * Super Admin → tous | Admin Centre → uniquement son centre.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $authUser = $request->user();
        $query = User::with('center');

        // Isolation multi-centres
        if ($authUser->isCenterAdmin()) {
            $query->where('center_id', $authUser->center_id);
        }

        // Filtre : recherche par nom ou email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtre : rôle
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        // Filtre : statut
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filtre : centre (Super Admin seulement)
        if ($authUser->isSuperAdmin() && ($centerId = $request->input('center_id'))) {
            $query->where('center_id', $centerId);
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();
        $centers = $authUser->isSuperAdmin() ? Center::orderBy('name')->get() : collect();

        return view('users.index', compact('users', 'centers'));
    }

    /**
     * Formulaire de création d'un utilisateur.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', User::class);

        $authUser = $request->user();
        $centers = $authUser->isSuperAdmin() ? Center::orderBy('name')->get() : collect();

        // Rôles autorisés selon le créateur
        $allowedRoles = $this->getAllowedRoles($authUser);

        return view('users.create', compact('centers', 'allowedRoles'));
    }

    /**
     * Enregistre un nouvel utilisateur.
     * Sécurité : le center_id est contrôlé côté serveur pour les Admin Centre.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $authUser = $request->user();
        $data = $request->validated();

        // Hasher le mot de passe
        $data['password'] = Hash::make($data['password']);

        // Sécurité multi-centres : forcer le center_id pour Admin Centre
        if ($authUser->isCenterAdmin()) {
            $data['center_id'] = $authUser->center_id;

            // Admin Centre ne peut pas créer un super_admin ou center_admin
            if (in_array($data['role'], [User::ROLE_SUPER_ADMIN, User::ROLE_CENTER_ADMIN])) {
                abort(403, __('Vous n\'êtes pas autorisé à créer ce type de compte.'));
            }
        } else {
            // Super Admin : center_id = null pour super_admin, sinon validé par le formulaire
            if ($data['role'] === User::ROLE_SUPER_ADMIN) {
                $data['center_id'] = null;
            }
        }

        $user = User::create($data);

        return redirect()
            ->route('users.show', $user)
            ->with('success', __('L\'utilisateur « :name » a été créé avec succès.', ['name' => $user->name]));
    }

    /**
     * Affiche le détail d'un utilisateur.
     */
    public function show(Request $request, User $user): View
    {
        Gate::authorize('view', $user);

        return view('users.show', compact('user'));
    }

    /**
     * Formulaire de modification d'un utilisateur.
     */
    public function edit(Request $request, User $user): View
    {
        Gate::authorize('update', $user);

        $authUser = $request->user();
        $centers = $authUser->isSuperAdmin() ? Center::orderBy('name')->get() : collect();
        $allowedRoles = $this->getAllowedRoles($authUser, $user);

        return view('users.edit', compact('user', 'centers', 'allowedRoles'));
    }

    /**
     * Met à jour un utilisateur.
     * Sécurité : le center_id est contrôlé côté serveur.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $authUser = $request->user();
        $data = $request->validated();

        // Gestion du mot de passe (facultatif à la modification)
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        // Sécurité multi-centres : Admin Centre ne peut pas changer le centre
        if ($authUser->isCenterAdmin()) {
            // Forcer le center_id du centre de l'admin
            $data['center_id'] = $authUser->center_id;

            // Admin Centre ne peut pas attribuer un rôle super_admin ou center_admin
            if (in_array($data['role'], [User::ROLE_SUPER_ADMIN, User::ROLE_CENTER_ADMIN])) {
                abort(403, __('Vous n\'êtes pas autorisé à attribuer ce rôle.'));
            }
        } else {
            // Super Admin : center_id = null si rôle super_admin
            if ($data['role'] === User::ROLE_SUPER_ADMIN) {
                $data['center_id'] = null;
            }
        }

        $user->update($data);

        return redirect()
            ->route('users.show', $user)
            ->with('success', __('L\'utilisateur « :name » a été mis à jour avec succès.', ['name' => $user->name]));
    }

    /**
     * Supprime un utilisateur (Super Admin uniquement, après contrôle).
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $userName = $user->name;
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', __('L\'utilisateur « :userName » a été supprimé.', ['userName' => $userName]));
    }

    /**
     * Bascule le statut d'un utilisateur (active ↔ inactive).
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('toggleStatus', $user);

        $user->status = $user->isActive() ? User::STATUS_INACTIVE : User::STATUS_ACTIVE;
        $user->save();

        $label = $user->isActive() ? __('activé') : __('désactivé');

        return redirect()
            ->route('users.show', $user)
            ->with('success', __('L\'utilisateur « :name » a été :label.', ['name' => $user->name, 'label' => $label]));
    }

    /**
     * Retourne les rôles autorisés pour la création/modification selon le rôle du demandeur.
     */
    private function getAllowedRoles(User $authUser, ?User $targetUser = null): array
    {
        if ($authUser->isSuperAdmin()) {
            return [
                User::ROLE_SUPER_ADMIN => __('Super Admin'),
                User::ROLE_CENTER_ADMIN => __('Admin Centre'),
                User::ROLE_TEACHER => 'Professeur',
                User::ROLE_STUDENT => __('Élève'),
            ];
        }

        // Admin Centre
        return [
            User::ROLE_TEACHER => 'Professeur',
            User::ROLE_STUDENT => __('Élève'),
        ];
    }
}
