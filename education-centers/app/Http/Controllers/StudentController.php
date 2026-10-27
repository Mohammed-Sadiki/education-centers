<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Student;
use App\Support\DomainRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Liste des dossiers étudiants avec recherche, filtres et pagination.
     * Super Admin -> tous | Admin Centre -> uniquement son centre.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Student::class);

        $authUser = $request->user();
        $query = Student::with(['user', 'center']);

        // Isolation multi-centres stricte
        if ($authUser->isCenterAdmin()) {
            $query->where('center_id', $authUser->center_id);
        }

        // Filtrage par domaine actif (Super Admin / Admin Centre en contexte domaine)
        $query = $this->applyDomainScope($query);

        // Filtre : recherche par numéro étudiant
        if ($studentNumber = $request->input('student_number')) {
            $query->where('student_number', 'like', "%{$studentNumber}%");
        }

        // Filtre : recherche par nom / prénom
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // Filtre : email (via user)
        if ($email = $request->input('email')) {
            $query->whereHas('user', function ($q) use ($email) {
                $q->where('email', 'like', "%{$email}%");
            });
        }

        // Filtre : statut
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filtre : centre (Super Admin seulement)
        if ($authUser->isSuperAdmin() && ($centerId = $request->input('center_id'))) {
            $query->where('center_id', $centerId);
        }

        $students = $query->latest('id')->paginate(15)->withQueryString();
        $centers = $authUser->isSuperAdmin() ? Center::orderBy('name')->get() : collect();

        return view('students.index', compact('students', 'centers'));
    }

    /**
     * Formulaire de création indépendante d'un élève (centralisé depuis la page
     * Domaines). Aucun compte de connexion n'est requis : le dossier est créé
     * directement. Cascade : centre (Super Admin) → domaine → formation → dossier.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Student::class);

        $authUser = $request->user();

        // Liste des centres : sélectionnable uniquement par le Super Admin.
        $centers = $authUser->isCenterAdmin() ? collect() : Center::orderBy('name')->get();

        // Catalogue des domaines (commun aux centres, source de vérité `domains`).
        $domains = $authUser->isCenterAdmin()
            ? DomainRegistry::visibleForCenter((int) $authUser->center_id)
            : DomainRegistry::visible();

        // Catalogue des formations actives, restreint au centre pour un Admin Centre.
        $formationsQuery = Formation::query()
            ->where('status', 'active')
            ->orderBy('name');

        if ($authUser->isCenterAdmin()) {
            $formationsQuery->where('center_id', $authUser->center_id);
        }

        // Données plates pour la cascade dynamique (Alpine) centre → domaine → formation.
        $formationsData = $formationsQuery
            ->get(['id', 'center_id', 'domain', 'name'])
            ->map(fn (Formation $formation): array => [
                'id'        => $formation->id,
                'center_id' => $formation->center_id,
                'domain'    => $formation->domain,
                'name'      => $formation->translated('name'),
            ])
            ->values();

        return view('students.create', compact(
            'centers',
            'domains',
            'formationsData',
        ));
    }

    /**
     * Enregistrer un nouveau dossier étudiant (+ inscription à la formation
     * sélectionnée, avec la logique métier existante des inscriptions).
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $authUser = $request->user();

        // Détermination stricte du center_id côté serveur. Le compte utilisateur
        // n'entre plus en compte : Super Admin = centre choisi (obligatoire à la
        // validation), Admin Centre = son propre centre, quelle que soit la requête.
        $centerId = $authUser->isCenterAdmin()
            ? $authUser->center_id
            : $request->validated('center_id');
        $center = Center::findOrFail($centerId);

        // Génération automatique du student_number côté serveur
        $studentNumber = Student::generateStudentNumber($center);

        $data = $request->validated();
        unset($data['domain'], $data['formation_id'], $data['center_id']);
        $data['center_id'] = $centerId;
        $data['student_number'] = $studentNumber;
        // user_id est facultatif : null = dossier créé sans compte de connexion.
        $data['user_id'] = $data['user_id'] ?? null;

        $formationId = $request->validated('formation_id');

        [$student, $enrollment] = DB::transaction(function () use ($data, $formationId, $center, $centerId): array {
            $student = Student::create($data);

            $enrollment = null;
            if ($formationId) {
                // Logique d'inscription existante : numéro généré, statut initial « en attente ».
                $enrollment = Enrollment::create([
                    'center_id'           => $centerId,
                    'student_id'          => $student->id,
                    'formation_id'        => $formationId,
                    'application_date'    => now()->toDateString(),
                    'status'              => Enrollment::STATUS_PENDING,
                    'registration_number' => Enrollment::generateRegistrationNumber($center),
                ]);
            }

            return [$student, $enrollment];
        });

        $message = __('Le dossier étudiant :student_number (:full_name) a été créé avec succès.', [
            'student_number' => $student->student_number,
            'full_name'      => $student->full_name,
        ]);

        if ($enrollment) {
            $message .= ' ' . __('Inscription :registration_number créée en attente de traitement.', [
                'registration_number' => $enrollment->registration_number,
            ]);
        }

        return redirect()
            ->route('students.show', $student)
            ->with('success', $message);
    }

    /**
     * Consulter un dossier étudiant.
     */
    public function show(Student $student): View
    {
        Gate::authorize('view', $student);

        $student->load(['user', 'center']);

        return view('students.show', compact('student'));
    }

    /**
     * Formulaire d'édition d'un dossier étudiant.
     */
    public function edit(Student $student): View
    {
        Gate::authorize('update', $student);

        $student->load(['user', 'center']);

        return view('students.edit', compact('student'));
    }

    /**
     * Mettre à jour un dossier étudiant.
     */
    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $student->update($request->validated());

        return redirect()
            ->route('students.show', $student)
            ->with('success', __('Le dossier étudiant :student_number a été mis à jour avec succès.', ['student_number' => $student->student_number]));
    }

    /**
     * Activer ou désactiver un dossier étudiant.
     */
    public function toggleStatus(Request $request, Student $student): RedirectResponse
    {
        Gate::authorize('toggleStatus', $student);

        $newStatus = $student->isActive() ? 'inactive' : 'active';
        $student->update(['status' => $newStatus]);

        $message = $newStatus === 'active'
            ? __('Le dossier étudiant :student_number a été activé.', ['student_number' => $student->student_number])
            : __('Le dossier étudiant :student_number a été désactivé.', ['student_number' => $student->student_number]);

        return back()->with('success', $message);
    }

    /**
     * Supprimer un dossier étudiant si nécessaire.
     */
    public function destroy(Student $student): RedirectResponse
    {
        Gate::authorize('delete', $student);

        $studentNumber = $student->student_number;
        $student->delete();

        return redirect()
            ->route('students.index')
            ->with('success', __('Le dossier étudiant :studentNumber a été supprimé.', ['studentNumber' => $studentNumber]));
    }
}
