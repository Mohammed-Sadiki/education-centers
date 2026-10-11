<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassRequest;
use App\Http\Requests\UpdateClassRequest;
use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Formation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClassController extends Controller
{
    /**
     * Liste des classes avec filtres et pagination.
     * Super Admin -> tous | Admin Centre -> uniquement son centre.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ClassRoom::class);

        $authUser = $request->user();
        $query = ClassRoom::with(['center', 'formation']);

        // Isolation multi-centres stricte
        if ($authUser->isCenterAdmin()) {
            $query->where('center_id', $authUser->center_id);
        }

        // Filtre : recherche par nom
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Filtre : code
        if ($code = $request->input('code')) {
            $query->where('code', 'like', "%{$code}%");
        }

        // Filtre : formation
        if ($formationId = $request->input('formation_id')) {
            $query->where('formation_id', $formationId);
        }

        // Filtre : statut
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filtre : centre (Super Admin seulement)
        if ($authUser->isSuperAdmin() && ($centerId = $request->input('center_id'))) {
            $query->where('center_id', $centerId);
        }

        $classes = $query->latest('id')->paginate(15)->withQueryString();

        // Récupération des filtres sélecteurs
        if ($authUser->isSuperAdmin()) {
            $centers = Center::orderBy('name')->get();
            $formations = Formation::orderBy('name')->get();
        } else {
            $centers = collect();
            $formations = Formation::where('center_id', $authUser->center_id)->orderBy('name')->get();
        }

        return view('classes.index', compact('classes', 'centers', 'formations'));
    }

    /**
     * Formulaire de création d'une classe.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', ClassRoom::class);

        $authUser = $request->user();

        if ($authUser->isSuperAdmin()) {
            $centers = Center::orderBy('name')->get();
            $formations = Formation::with('center')->orderBy('name')->get();
        } else {
            $centers = collect();
            $formations = Formation::where('center_id', $authUser->center_id)->orderBy('name')->get();
        }

        return view('classes.create', compact('centers', 'formations'));
    }

    /**
     * Enregistrer une nouvelle classe.
     */
    public function store(StoreClassRequest $request): RedirectResponse
    {
        $authUser = $request->user();

        // Détermination stricte du center_id côté serveur (jamais faire confiance au client)
        $centerId = $authUser->isCenterAdmin() ? $authUser->center_id : (int) $request->input('center_id');

        $data = $request->validated();
        $data['center_id'] = $centerId;

        $classRoom = ClassRoom::create($data);

        return redirect()
            ->route('classes.show', $classRoom)
            ->with('success', "La classe \"{$classRoom->name}\" ({$classRoom->code}) a été créée avec succès.");
    }

    /**
     * Consulter une classe.
     */
    public function show(ClassRoom $class): View
    {
        Gate::authorize('view', $class);

        $class->load(['center', 'formation']);

        return view('classes.show', compact('class'));
    }

    /**
     * Formulaire d'édition d'une classe.
     */
    public function edit(ClassRoom $class): View
    {
        Gate::authorize('update', $class);

        $authUser = request()->user();
        $class->load(['center', 'formation']);

        if ($authUser->isSuperAdmin()) {
            $formations = Formation::where('center_id', $class->center_id)->orderBy('name')->get();
        } else {
            $formations = Formation::where('center_id', $authUser->center_id)->orderBy('name')->get();
        }

        return view('classes.edit', compact('class', 'formations'));
    }

    /**
     * Mettre à jour une classe.
     */
    public function update(UpdateClassRequest $request, ClassRoom $class): RedirectResponse
    {
        $class->update($request->validated());

        return redirect()
            ->route('classes.show', $class)
            ->with('success', "La classe \"{$class->name}\" a été mise à jour avec succès.");
    }

    /**
     * Activer ou désactiver une classe.
     */
    public function toggleStatus(Request $request, ClassRoom $class): RedirectResponse
    {
        Gate::authorize('toggleStatus', $class);

        $newStatus = $class->isActive() ? 'inactive' : 'active';
        $class->update(['status' => $newStatus]);

        $message = $newStatus === 'active'
            ? "La classe \"{$class->name}\" a été activée."
            : "La classe \"{$class->name}\" a été désactivée.";

        return back()->with('success', $message);
    }

    /**
     * Supprimer une classe si aucune dépendance ne l'empêche.
     */
    public function destroy(ClassRoom $class): RedirectResponse
    {
        Gate::authorize('delete', $class);

        // Protection contre les dépendances futures (inscriptions, présences, etc.)
        if (method_exists($class, 'enrollments') && $class->enrollments()->exists()) {
            return back()->with('error', "Impossible de supprimer la classe : des inscriptions y sont associées.");
        }

        $name = $class->name;
        $class->delete();

        return redirect()
            ->route('classes.index')
            ->with('success', "La classe \"{$name}\" a été supprimée avec succès.");
    }
}
