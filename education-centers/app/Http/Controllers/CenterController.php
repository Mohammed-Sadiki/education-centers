<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCenterRequest;
use App\Http\Requests\UpdateCenterRequest;
use App\Models\Center;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CenterController extends Controller
{
    /**
     * Affiche la liste de tous les centres.
     */
    public function index(): View
    {
        $centers = Center::orderBy('name')->paginate(15);

        return view('centers.index', compact('centers'));
    }

    /**
     * Affiche le formulaire de création d'un centre.
     */
    public function create(): View
    {
        return view('centers.create');
    }

    /**
     * Enregistre un nouveau centre en base de données.
     */
    public function store(StoreCenterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Traitement du logo
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('centers/logos', 'public');
        }

        $center = Center::create($data);

        return redirect()
            ->route('centers.show', $center)
            ->with('success', __('Le centre « :name » a été créé avec succès.', ['name' => $center->name]));
    }

    /**
     * Affiche le détail d'un centre.
     */
    public function show(Center $center): View
    {
        // Chargement du nombre d'utilisateurs liés
        $usersCount = $center->users()->count();

        return view('centers.show', compact('center', 'usersCount'));
    }

    /**
     * Affiche le formulaire de modification d'un centre.
     */
    public function edit(Center $center): View
    {
        return view('centers.edit', compact('center'));
    }

    /**
     * Met à jour les informations d'un centre.
     */
    public function update(UpdateCenterRequest $request, Center $center): RedirectResponse
    {
        $data = $request->validated();

        // Traitement du logo — si un nouveau logo est uploadé
        if ($request->hasFile('logo')) {
            // Supprimer l'ancien logo si existant
            if ($center->logo) {
                Storage::disk('public')->delete($center->logo);
            }
            $data['logo'] = $request->file('logo')->store('centers/logos', 'public');
        } else {
            // Conserver l'ancien logo
            unset($data['logo']);
        }

        $center->update($data);

        return redirect()
            ->route('centers.show', $center)
            ->with('success', __('Le centre « :name » a été mis à jour avec succès.', ['name' => $center->name]));
    }

    /**
     * Supprime un centre — avec protection des données liées.
     *
     * Règle métier :
     * - Si le centre possède des utilisateurs liés → désactivation uniquement.
     * - Si le centre est vide → suppression autorisée.
     */
    public function destroy(Center $center): RedirectResponse
    {
        // Protection : vérifier si des utilisateurs sont liés à ce centre
        if ($center->hasLinkedUsers()) {
            return redirect()
                ->route('centers.show', $center)
                ->with(
                    'error',
                    __('Impossible de supprimer le centre « :name » : des utilisateurs y sont rattachés. ', ['name' => $center->name]).
                    __('Désactivez-le plutôt ou transférez les utilisateurs.')
                );
        }

        $centerName = $center->name;

        // Supprimer le logo associé
        if ($center->logo) {
            Storage::disk('public')->delete($center->logo);
        }

        $center->delete();

        return redirect()
            ->route('centers.index')
            ->with('success', __('Le centre « :centerName » a été supprimé définitivement.', ['centerName' => $centerName]));
    }

    /**
     * Bascule le statut d'un centre (active ↔ inactive).
     * Cette action est plus sûre que la suppression.
     */
    public function toggleStatus(Center $center): RedirectResponse
    {
        $center->status = $center->isActive() ? 'inactive' : 'active';
        $center->save();

        $label = $center->isActive() ? __('activé') : __('désactivé');

        return redirect()
            ->route('centers.show', $center)
            ->with('success', __('Le centre « :name » a été :label.', ['name' => $center->name, 'label' => $label]));
    }
}
