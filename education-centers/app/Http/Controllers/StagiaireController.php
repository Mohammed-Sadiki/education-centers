<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStagiaireRequest;
use App\Http\Requests\UpdateStagiaireRequest;
use App\Models\Center;
use App\Models\Stagiaire;
use App\Models\StagiaireDiploma;
use App\Models\StagiaireDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StagiaireController extends Controller
{
    /**
     * Mini-dashboard spécifique aux Stagiaires.
     */
    public function dashboard(Request $request): View
    {
        Gate::authorize('viewAny', Stagiaire::class);

        $authUser = $request->user();
        $query = Stagiaire::query();

        if ($authUser->isCenterAdmin()) {
            $query->where('center_id', $authUser->center_id);
        }

        $total = (clone $query)->count();
        $nouveaux = (clone $query)->where('status', 'nouveau')->count();
        $enCours = (clone $query)->where('status', 'en_cours')->count();
        $termines = (clone $query)->where('status', 'termine')->count();

        // Critère d'un dossier complet : CIN renseigné, CIN recto téléversé, dates de stage définies et prix renseigné
        $dossiersComplets = (clone $query)
            ->whereNotNull('cin_number')
            ->whereNotNull('cin_recto_path')
            ->whereNotNull('stage_start_date')
            ->whereNotNull('stage_end_date')
            ->count();

        $dossiersIncomplets = max(0, $total - $dossiersComplets);

        $recentStagiaires = (clone $query)
            ->with(['center', 'diplomas', 'documents'])
            ->latest()
            ->take(6)
            ->get();

        return view('super-admin.stagiaires.dashboard', compact(
            'total',
            'nouveaux',
            'enCours',
            'termines',
            'dossiersComplets',
            'dossiersIncomplets',
            'recentStagiaires'
        ));
    }

    /**
     * Liste des stagiaires avec recherche, filtres et pagination.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Stagiaire::class);

        $authUser = $request->user();
        $baseQuery = Stagiaire::query();

        if ($authUser->isCenterAdmin()) {
            $baseQuery->where('center_id', $authUser->center_id);
        }

        $totalCount = (clone $baseQuery)->count();
        $completsCount = (clone $baseQuery)
            ->whereNotNull('cin_number')
            ->whereNotNull('cin_recto_path')
            ->whereNotNull('stage_start_date')
            ->whereNotNull('stage_end_date')
            ->count();
        $incompletsCount = max(0, $totalCount - $completsCount);

        $query = Stagiaire::with(['center', 'diplomas', 'documents']);

        if ($authUser->isCenterAdmin()) {
            $query->where('center_id', $authUser->center_id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('cin_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($filter = $request->input('filter')) {
            if ($filter === 'complets') {
                $query->whereNotNull('cin_number')
                      ->whereNotNull('cin_recto_path')
                      ->whereNotNull('stage_start_date')
                      ->whereNotNull('stage_end_date');
            } elseif ($filter === 'incomplets') {
                $query->where(function ($q) {
                    $q->whereNull('cin_number')
                      ->orWhereNull('cin_recto_path')
                      ->orWhereNull('stage_start_date')
                      ->orWhereNull('stage_end_date');
                });
            }
        }

        if ($authUser->isSuperAdmin() && ($centerId = $request->input('center_id'))) {
            $query->where('center_id', $centerId);
        }

        $stagiaires = $query->latest('id')->paginate(12)->withQueryString();
        $centers = $authUser->isSuperAdmin() ? Center::orderBy('name')->get() : collect();

        return view('super-admin.stagiaires.index', compact(
            'stagiaires',
            'centers',
            'totalCount',
            'completsCount',
            'incompletsCount',
            'filter'
        ));
    }

    /**
     * Espace centralisé de consultation et gestion des documents des stagiaires.
     */
    public function documents(Request $request): View
    {
        Gate::authorize('viewAny', Stagiaire::class);

        $authUser = $request->user();
        $typeFilter = $request->input('type');
        $search = $request->input('search');

        // Récupérer les documents administratifs
        $docsQuery = StagiaireDocument::with('stagiaire');
        if ($authUser->isCenterAdmin()) {
            $docsQuery->whereHas('stagiaire', function ($q) use ($authUser) {
                $q->where('center_id', $authUser->center_id);
            });
        }
        if ($search) {
            $docsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('stagiaire', function ($sq) use ($search) {
                      $sq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }
        if ($typeFilter && !in_array($typeFilter, ['cin', 'diploma'])) {
            $docsQuery->where('type', $typeFilter);
        }

        // Récupérer les diplômes
        $diplomasQuery = StagiaireDiploma::with('stagiaire')->whereNotNull('file_path');
        if ($authUser->isCenterAdmin()) {
            $diplomasQuery->whereHas('stagiaire', function ($q) use ($authUser) {
                $q->where('center_id', $authUser->center_id);
            });
        }
        if ($search) {
            $diplomasQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('institution', 'like', "%{$search}%")
                  ->orWhereHas('stagiaire', function ($sq) use ($search) {
                      $sq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // Récupérer les stagiaires avec CIN téléversé
        $cinQuery = Stagiaire::query()->where(function ($q) {
            $q->whereNotNull('cin_recto_path')->orWhereNotNull('cin_verso_path');
        });
        if ($authUser->isCenterAdmin()) {
            $cinQuery->where('center_id', $authUser->center_id);
        }
        if ($search) {
            $cinQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('cin_number', 'like', "%{$search}%");
            });
        }

        $documents = ($typeFilter && in_array($typeFilter, ['cin', 'diploma'])) ? collect() : $docsQuery->latest()->get();
        $diplomas = ($typeFilter && $typeFilter !== 'diploma') ? collect() : $diplomasQuery->latest()->get();
        $cins = ($typeFilter && $typeFilter !== 'cin') ? collect() : $cinQuery->latest()->get();

        $totalDocsCount = StagiaireDocument::count() + StagiaireDiploma::whereNotNull('file_path')->count() + Stagiaire::whereNotNull('cin_recto_path')->count();

        return view('super-admin.stagiaires.documents', compact(
            'documents',
            'diplomas',
            'cins',
            'typeFilter',
            'search',
            'totalDocsCount'
        ));
    }

    /**
     * Formulaire d'ajout d'un nouveau stagiaire.
     */
    public function create(): View
    {
        Gate::authorize('create', Stagiaire::class);

        $centers = Center::orderBy('name')->get();

        return view('super-admin.stagiaires.create', compact('centers'));
    }

    /**
     * Enregistrer un nouveau stagiaire.
     */
    public function store(StoreStagiaireRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['cin_recto', 'cin_verso', 'diplomas', 'other_documents']);

        // Upload CIN recto et verso dans l'espace privé
        if ($request->hasFile('cin_recto')) {
            $data['cin_recto_path'] = $request->file('cin_recto')->store('stagiaires/cin', 'local');
        }

        if ($request->hasFile('cin_verso')) {
            $data['cin_verso_path'] = $request->file('cin_verso')->store('stagiaires/cin', 'local');
        }

        $stagiaire = Stagiaire::create($data);

        // Enregistrement des diplômes multiples
        if ($request->has('diplomas') && is_array($request->diplomas)) {
            foreach ($request->diplomas as $index => $dip) {
                if (empty($dip['title'])) {
                    continue;
                }

                $filePath = null;
                $fileName = null;

                $file = $request->file("diplomas.{$index}.file")
                    ?? ($request->file('diplomas')[$index]['file'] ?? null)
                    ?? data_get($request->allFiles(), "diplomas.{$index}.file")
                    ?? (isset($dip['file']) && $dip['file'] instanceof \Illuminate\Http\UploadedFile ? $dip['file'] : null);

                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $fileName = $file->getClientOriginalName();
                    $filePath = $file->store("stagiaires/{$stagiaire->id}/diplomas", 'local');
                }

                $stagiaire->diplomas()->create([
                    'title'       => $dip['title'],
                    'institution' => $dip['institution'] ?? null,
                    'year'        => $dip['year'] ?? null,
                    'file_path'   => $filePath,
                    'file_name'   => $fileName,
                ]);
            }
        }

        // Enregistrement des autres documents
        if ($request->has('other_documents') && is_array($request->other_documents)) {
            foreach ($request->other_documents as $index => $doc) {
                if (empty($doc['title'])) {
                    continue;
                }

                $filePath = null;
                $fileName = null;

                $file = $request->file("other_documents.{$index}.file")
                    ?? ($request->file('other_documents')[$index]['file'] ?? null)
                    ?? data_get($request->allFiles(), "other_documents.{$index}.file")
                    ?? (isset($doc['file']) && $doc['file'] instanceof \Illuminate\Http\UploadedFile ? $doc['file'] : null);

                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $fileName = $file->getClientOriginalName();
                    $filePath = $file->store("stagiaires/{$stagiaire->id}/documents", 'local');
                }

                if ($filePath) {
                    $stagiaire->documents()->create([
                        'title'     => $doc['title'],
                        'type'      => $doc['type'] ?? 'autre',
                        'file_path' => $filePath,
                        'file_name' => $fileName,
                    ]);
                }
            }
        }

        return redirect()
            ->route('stagiaires.show', $stagiaire)
            ->with('success', "Le dossier du stagiaire {$stagiaire->full_name} a été créé avec succès.");
    }

    /**
     * Détail du stagiaire.
     */
    public function show(Stagiaire $stagiaire): View
    {
        Gate::authorize('view', $stagiaire);

        $stagiaire->load(['center', 'diplomas', 'documents']);

        return view('super-admin.stagiaires.show', compact('stagiaire'));
    }

    /**
     * Formulaire d'édition du stagiaire.
     */
    public function edit(Stagiaire $stagiaire): View
    {
        Gate::authorize('update', $stagiaire);

        $stagiaire->load(['diplomas', 'documents']);
        $centers = Center::orderBy('name')->get();

        return view('super-admin.stagiaires.edit', compact('stagiaire', 'centers'));
    }

    /**
     * Mettre à jour le dossier du stagiaire.
     */
    public function update(UpdateStagiaireRequest $request, Stagiaire $stagiaire): RedirectResponse
    {
        $data = $request->safe()->except(['cin_recto', 'cin_verso', 'diplomas', 'other_documents']);

        if ($request->hasFile('cin_recto')) {
            if ($stagiaire->cin_recto_path && Storage::disk('local')->exists($stagiaire->cin_recto_path)) {
                Storage::disk('local')->delete($stagiaire->cin_recto_path);
            }
            $data['cin_recto_path'] = $request->file('cin_recto')->store('stagiaires/cin', 'local');
        }

        if ($request->hasFile('cin_verso')) {
            if ($stagiaire->cin_verso_path && Storage::disk('local')->exists($stagiaire->cin_verso_path)) {
                Storage::disk('local')->delete($stagiaire->cin_verso_path);
            }
            $data['cin_verso_path'] = $request->file('cin_verso')->store('stagiaires/cin', 'local');
        }

        $stagiaire->update($data);

        // Ajout de nouveaux diplômes si envoyés
        if ($request->has('diplomas') && is_array($request->diplomas)) {
            foreach ($request->diplomas as $index => $dip) {
                if (empty($dip['title'])) {
                    continue;
                }

                $filePath = null;
                $fileName = null;

                $file = $request->file("diplomas.{$index}.file")
                    ?? ($request->file('diplomas')[$index]['file'] ?? null)
                    ?? data_get($request->allFiles(), "diplomas.{$index}.file")
                    ?? (isset($dip['file']) && $dip['file'] instanceof \Illuminate\Http\UploadedFile ? $dip['file'] : null);

                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $fileName = $file->getClientOriginalName();
                    $filePath = $file->store("stagiaires/{$stagiaire->id}/diplomas", 'local');
                }

                $stagiaire->diplomas()->create([
                    'title'       => $dip['title'],
                    'institution' => $dip['institution'] ?? null,
                    'year'        => $dip['year'] ?? null,
                    'file_path'   => $filePath,
                    'file_name'   => $fileName,
                ]);
            }
        }

        // Ajout de nouveaux documents si envoyés
        if ($request->has('other_documents') && is_array($request->other_documents)) {
            foreach ($request->other_documents as $index => $doc) {
                if (empty($doc['title'])) {
                    continue;
                }

                $filePath = null;
                $fileName = null;

                $file = $request->file("other_documents.{$index}.file")
                    ?? ($request->file('other_documents')[$index]['file'] ?? null)
                    ?? data_get($request->allFiles(), "other_documents.{$index}.file")
                    ?? (isset($doc['file']) && $doc['file'] instanceof \Illuminate\Http\UploadedFile ? $doc['file'] : null);

                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $fileName = $file->getClientOriginalName();
                    $filePath = $file->store("stagiaires/{$stagiaire->id}/documents", 'local');
                }

                if ($filePath) {
                    $stagiaire->documents()->create([
                        'title'     => $doc['title'],
                        'type'      => $doc['type'] ?? 'autre',
                        'file_path' => $filePath,
                        'file_name' => $fileName,
                    ]);
                }
            }
        }

        return redirect()
            ->route('stagiaires.show', $stagiaire)
            ->with('success', "Le dossier de {$stagiaire->full_name} a été mis à jour avec succès.");
    }

    /**
     * Supprimer un stagiaire et ses documents associés.
     */
    public function destroy(Stagiaire $stagiaire): RedirectResponse
    {
        Gate::authorize('delete', $stagiaire);

        // Nettoyer les fichiers CIN
        if ($stagiaire->cin_recto_path && Storage::disk('local')->exists($stagiaire->cin_recto_path)) {
            Storage::disk('local')->delete($stagiaire->cin_recto_path);
        }
        if ($stagiaire->cin_verso_path && Storage::disk('local')->exists($stagiaire->cin_verso_path)) {
            Storage::disk('local')->delete($stagiaire->cin_verso_path);
        }

        // Nettoyer le dossier des documents du stagiaire
        Storage::disk('local')->deleteDirectory("stagiaires/{$stagiaire->id}");

        $fullName = $stagiaire->full_name;
        $stagiaire->delete();

        return redirect()
            ->route('stagiaires.index')
            ->with('success', "Le dossier du stagiaire {$fullName} a été supprimé.");
    }

    /**
     * Téléchargement sécurisé du document CIN (recto ou verso).
     */
    public function downloadCin(Stagiaire $stagiaire, string $side): BinaryFileResponse
    {
        Gate::authorize('view', $stagiaire);

        $path = $side === 'recto' ? $stagiaire->cin_recto_path : $stagiaire->cin_verso_path;

        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404, 'Fichier introuvable.');
        }

        return response()->download(Storage::disk('local')->path($path));
    }

    /**
     * Téléchargement sécurisé d'un diplôme.
     */
    public function downloadDiploma(Stagiaire $stagiaire, StagiaireDiploma $diploma): BinaryFileResponse
    {
        Gate::authorize('view', $stagiaire);

        if ($diploma->stagiaire_id !== $stagiaire->id || !$diploma->file_path || !Storage::disk('local')->exists($diploma->file_path)) {
            abort(404, 'Fichier introuvable.');
        }

        return response()->download(
            Storage::disk('local')->path($diploma->file_path),
            $diploma->file_name ?? basename($diploma->file_path)
        );
    }

    /**
     * Téléchargement sécurisé d'un document administratif.
     */
    public function downloadDocument(Stagiaire $stagiaire, StagiaireDocument $document): BinaryFileResponse
    {
        Gate::authorize('view', $stagiaire);

        if ($document->stagiaire_id !== $stagiaire->id || !$document->file_path || !Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Fichier introuvable.');
        }

        return response()->download(
            Storage::disk('local')->path($document->file_path),
            $document->file_name ?? basename($document->file_path)
        );
    }

    /**
     * Supprimer un diplôme spécifique.
     */
    public function deleteDiploma(Stagiaire $stagiaire, StagiaireDiploma $diploma): RedirectResponse
    {
        Gate::authorize('update', $stagiaire);

        if ($diploma->stagiaire_id === $stagiaire->id) {
            if ($diploma->file_path && Storage::disk('local')->exists($diploma->file_path)) {
                Storage::disk('local')->delete($diploma->file_path);
            }
            $diploma->delete();
        }

        return back()->with('success', 'Diplôme supprimé.');
    }

    /**
     * Supprimer un document spécifique.
     */
    public function deleteDocument(Stagiaire $stagiaire, StagiaireDocument $document): RedirectResponse
    {
        Gate::authorize('update', $stagiaire);

        if ($document->stagiaire_id === $stagiaire->id) {
            if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }
            $document->delete();
        }

        return back()->with('success', 'Document supprimé.');
    }
}
