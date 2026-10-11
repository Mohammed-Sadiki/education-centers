<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * Liste des séances du planning (avec filtres).
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Schedule::class);

        $authUser = $request->user();

        $query = Schedule::query()->with([
            'center',
            'classRoom.formation',
            'teacher.user',
            'room',
        ]);

        // Isolation stricte par centre
        if ($authUser->isCenterAdmin()) {
            $query->where('center_id', $authUser->center_id);
        }

        // Filtre : centre (Super Admin seulement)
        if ($authUser->isSuperAdmin() && ($centerId = $request->input('center_id'))) {
            $query->where('center_id', $centerId);
        }

        // Filtre : date précise
        if ($date = $request->input('date')) {
            $query->whereDate('date', $date);
        }

        // Filtre : statut
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filtre : classe
        if ($classId = $request->input('class_id')) {
            $query->where('class_id', $classId);
        }

        // Filtre : professeur
        if ($teacherId = $request->input('teacher_id')) {
            $query->where('teacher_id', $teacherId);
        }

        // Filtre : salle
        if ($roomId = $request->input('room_id')) {
            $query->where('room_id', $roomId);
        }

        $schedules = $query->orderBy('date', 'desc')
            ->orderBy('start_time', 'asc')
            ->paginate(15)
            ->withQueryString();

        // Données pour les filtres
        $targetCenterId = $authUser->isCenterAdmin() ? $authUser->center_id : $request->input('center_id');

        $classesQuery = ClassRoom::query()->orderBy('name');
        $teachersQuery = Teacher::query()->orderBy('first_name');
        $roomsQuery = Room::query()->orderBy('name');

        if ($targetCenterId) {
            $classesQuery->where('center_id', $targetCenterId);
            $teachersQuery->where('center_id', $targetCenterId);
            $roomsQuery->where('center_id', $targetCenterId);
        }

        $classes = $classesQuery->get();
        $teachers = $teachersQuery->get();
        $rooms = $roomsQuery->get();
        $centers = $authUser->isSuperAdmin() ? Center::orderBy('name')->get() : collect();

        return view('schedules.index', compact('schedules', 'classes', 'teachers', 'rooms', 'centers'));
    }

    /**
     * Formulaire de création d'une séance.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Schedule::class);

        $authUser = $request->user();
        $centers = $authUser->isSuperAdmin() ? Center::orderBy('name')->get() : collect();

        // Déterminer le centre de référence pour charger les listes déroulantes
        $selectedCenterId = $authUser->isCenterAdmin() 
            ? $authUser->center_id 
            : ($request->input('center_id') ?? $centers->first()?->id);

        $classes = ClassRoom::query()
            ->when($selectedCenterId, fn($q) => $q->where('center_id', $selectedCenterId))
            ->where('status', 'active')
            ->with('formation', 'teachers')
            ->orderBy('name')
            ->get();

        $rooms = Room::query()
            ->when($selectedCenterId, fn($q) => $q->where('center_id', $selectedCenterId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $teachers = Teacher::query()
            ->when($selectedCenterId, fn($q) => $q->where('center_id', $selectedCenterId))
            ->where('status', 'active')
            ->with('classes')
            ->orderBy('first_name')
            ->get();

        $preselectedClassId = $request->input('class_id');

        return view('schedules.create', compact('centers', 'classes', 'rooms', 'teachers', 'selectedCenterId', 'preselectedClassId'));
    }

    /**
     * Enregistrer une nouvelle séance.
     */
    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        $authUser = $request->user();

        // Détermination stricte du center_id côté serveur
        $centerId = $authUser->isCenterAdmin() ? $authUser->center_id : (int) $request->input('center_id');

        $data = $request->validated();
        $data['center_id'] = $centerId;

        $schedule = Schedule::create($data);

        return redirect()
            ->route('schedules.show', $schedule)
            ->with('success', 'La séance a été programmée avec succès.');
    }

    /**
     * Fiche d'une séance.
     */
    public function show(Schedule $schedule): View
    {
        Gate::authorize('view', $schedule);

        $schedule->load(['center', 'classRoom.formation', 'teacher.user', 'room']);

        return view('schedules.show', compact('schedule'));
    }

    /**
     * Formulaire d'édition d'une séance.
     */
    public function edit(Schedule $schedule): View
    {
        Gate::authorize('update', $schedule);

        $schedule->load(['center', 'classRoom', 'teacher', 'room']);
        $authUser = request()->user();
        $centers = $authUser->isSuperAdmin() ? Center::orderBy('name')->get() : collect();

        $centerId = $schedule->center_id;

        $classes = ClassRoom::query()
            ->where('center_id', $centerId)
            ->with('formation', 'teachers')
            ->orderBy('name')
            ->get();

        $rooms = Room::query()
            ->where('center_id', $centerId)
            ->orderBy('name')
            ->get();

        $teachers = Teacher::query()
            ->where('center_id', $centerId)
            ->with('classes')
            ->orderBy('first_name')
            ->get();

        return view('schedules.edit', compact('schedule', 'centers', 'classes', 'rooms', 'teachers'));
    }

    /**
     * Mettre à jour une séance.
     */
    public function update(UpdateScheduleRequest $request, Schedule $schedule): RedirectResponse
    {
        Gate::authorize('update', $schedule);

        $authUser = $request->user();
        $data = $request->validated();

        // Pour un Admin Centre, le center_id est immuable
        if ($authUser->isCenterAdmin()) {
            unset($data['center_id']);
        }

        $schedule->update($data);

        return redirect()
            ->route('schedules.show', $schedule)
            ->with('success', 'La séance a été modifiée avec succès.');
    }

    /**
     * Changer le statut d'une séance (annuler ou marquer terminée).
     */
    public function toggleStatus(Request $request, Schedule $schedule): RedirectResponse
    {
        Gate::authorize('toggleStatus', $schedule);

        $request->validate([
            'status' => ['required', 'string', 'in:scheduled,cancelled,completed'],
        ]);

        $newStatus = $request->input('status');
        $schedule->update(['status' => $newStatus]);

        $statusLabels = [
            'scheduled' => 'programmée',
            'cancelled' => 'annulée',
            'completed' => 'marquée comme terminée',
        ];

        return redirect()
            ->back()
            ->with('success', "La séance est désormais {$statusLabels[$newStatus]}.");
    }

    /**
     * Supprimer une séance.
     */
    public function destroy(Schedule $schedule): RedirectResponse
    {
        Gate::authorize('delete', $schedule);

        $schedule->delete();

        return redirect()
            ->route('schedules.index')
            ->with('success', 'La séance a été supprimée avec succès.');
    }
}
