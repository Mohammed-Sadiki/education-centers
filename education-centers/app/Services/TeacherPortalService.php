<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\DomainRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Contexte et requêtes de l'espace professeur.
 *
 * Toutes les méthodes partent de l'utilisateur authentifié puis du dossier
 * professeur lié. Aucun identifiant de professeur fourni par le navigateur
 * n'est utilisé pour déterminer le périmètre.
 */
class TeacherPortalService
{
    public function __construct(protected PersonalFinanceService $finance) {}

    /**
     * Résout le dossier professeur de l'utilisateur connecté.
     *
     * Le dossier doit appartenir au même centre que le compte utilisateur.
     * Un professeur sans dossier obtient un espace vide, jamais les données
     * d'un autre professeur.
     */
    public function teacherFor(User $user): ?Teacher
    {
        abort_unless($user->isTeacher(), 403);

        if (! $user->center_id) {
            return null;
        }

        return Teacher::query()
            ->where('user_id', $user->id)
            ->where('center_id', $user->center_id)
            ->with('center')
            ->first();
    }

    /**
     * Classes réellement affectées au professeur, dans son centre.
     */
    public function classesQuery(?Teacher $teacher): Builder
    {
        if (! $teacher) {
            return ClassRoom::query()->whereRaw('1 = 0');
        }

        return ClassRoom::query()
            ->where('classes.center_id', $teacher->center_id)
            ->whereHas('teachers', fn (Builder $query) => $query->where('teachers.id', $teacher->id))
            ->with(['formation.domainModel', 'center'])
            ->withCount([
                'enrollments as accepted_enrollments_count' => fn (Builder $query) => $query
                    ->where('status', Enrollment::STATUS_ACCEPTED)
                    ->where('center_id', $teacher->center_id),
            ]);
    }

    /**
     * @return EloquentCollection<int, ClassRoom>
     */
    public function classes(?Teacher $teacher): EloquentCollection
    {
        return $this->classesQuery($teacher)
            ->orderBy('name')
            ->get();
    }

    /**
     * Retrouve une classe affectée au professeur.
     */
    public function classFor(?Teacher $teacher, int $classId): ?ClassRoom
    {
        if (! $teacher) {
            return null;
        }

        return $this->classesQuery($teacher)->find($classId);
    }

    /**
     * Refuse explicitement une classe hors centre ou non affectée.
     */
    public function assertClassAccess(?Teacher $teacher, ClassRoom $class): void
    {
        abort_unless(
            $teacher !== null
                && (int) $teacher->center_id === (int) $class->center_id
                && $class->hasTeacher($teacher),
            403
        );
    }

    /**
     * @return Collection<int, int>
     */
    public function classIds(?Teacher $teacher): Collection
    {
        if (! $teacher) {
            return collect();
        }

        return ClassRoom::query()
            ->where('center_id', $teacher->center_id)
            ->whereHas('teachers', fn (Builder $query) => $query->where('teachers.id', $teacher->id))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();
    }

    /**
     * Étudiants acceptés dans une classe affectée.
     */
    public function studentsQuery(?Teacher $teacher, ClassRoom $class): Builder
    {
        if (! $teacher || (int) $teacher->center_id !== (int) $class->center_id) {
            return Student::query()->whereRaw('1 = 0');
        }

        return Student::query()
            ->where('students.center_id', $teacher->center_id)
            ->whereHas('enrollments', function (Builder $query) use ($teacher, $class): void {
                $query->where('center_id', $teacher->center_id)
                    ->where('class_id', $class->id)
                    ->where('status', Enrollment::STATUS_ACCEPTED);
            })
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    /**
     * @return EloquentCollection<int, Student>
     */
    public function students(?Teacher $teacher, ClassRoom $class): EloquentCollection
    {
        return $this->studentsQuery($teacher, $class)->get();
    }

    /**
     * Nombre d'étudiants distincts inscrits dans les classes du professeur.
     */
    public function studentCount(?Teacher $teacher): int
    {
        if (! $teacher) {
            return 0;
        }

        $classIds = $this->classIds($teacher);

        if ($classIds->isEmpty()) {
            return 0;
        }

        return Student::query()
            ->where('center_id', $teacher->center_id)
            ->whereHas('enrollments', function (Builder $query) use ($teacher, $classIds): void {
                $query->where('center_id', $teacher->center_id)
                    ->whereIn('class_id', $classIds->all())
                    ->where('status', Enrollment::STATUS_ACCEPTED);
            })
            ->count();
    }

    /**
     * Domaines réellement enseignés (domaine principal + classes affectées).
     *
     * @return array<string, string>
     */
    public function domainLabels(?Teacher $teacher): array
    {
        if (! $teacher) {
            return [];
        }

        $labels = DomainRegistry::labels();
        $slugs = $teacher->domainSlugs();

        return $slugs
            ->mapWithKeys(fn (string $slug): array => [$slug => $labels[$slug] ?? ucfirst(str_replace('-', ' ', $slug))])
            ->all();
    }

    /**
     * Séances personnelles du professeur, uniquement dans ses classes.
     */
    public function schedulesQuery(?Teacher $teacher): Builder
    {
        if (! $teacher) {
            return Schedule::query()->whereRaw('1 = 0');
        }

        $classIds = $this->classIds($teacher);

        if ($classIds->isEmpty()) {
            return Schedule::query()->whereRaw('1 = 0');
        }

        return Schedule::query()
            ->where('center_id', $teacher->center_id)
            ->where('teacher_id', $teacher->id)
            ->whereIn('class_id', $classIds->all());
    }

    /**
     * Vérifie qu'une séance appartient bien au professeur et à une classe
     * qu'il enseigne. Refuse également un centre différent.
     */
    public function assertScheduleAccess(?Teacher $teacher, Schedule $schedule): void
    {
        abort_unless(
            $teacher !== null
                && (int) $schedule->center_id === (int) $teacher->center_id
                && (int) $schedule->teacher_id === (int) $teacher->id,
            403
        );

        $class = $schedule->classRoom;

        abort_unless($class !== null, 403);
        $this->assertClassAccess($teacher, $class);
    }

    /**
     * Présences visibles par le professeur : uniquement ses classes affectées.
     */
    public function attendancesQuery(?Teacher $teacher): Builder
    {
        if (! $teacher) {
            return Attendance::query()->whereRaw('1 = 0');
        }

        $classIds = $this->classIds($teacher);

        if ($classIds->isEmpty()) {
            return Attendance::query()->whereRaw('1 = 0');
        }

        return Attendance::query()
            ->where('center_id', $teacher->center_id)
            ->whereIn('class_id', $classIds->all());
    }

    /**
     * Évaluations réellement gérées par le professeur.
     */
    public function evaluationsQuery(?Teacher $teacher): Builder
    {
        if (! $teacher) {
            return Evaluation::query()->whereRaw('1 = 0');
        }

        $classIds = $this->classIds($teacher);

        if ($classIds->isEmpty()) {
            return Evaluation::query()->whereRaw('1 = 0');
        }

        return Evaluation::query()
            ->where('center_id', $teacher->center_id)
            ->where('teacher_id', $teacher->id)
            ->whereIn('class_id', $classIds->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardData(User $user): array
    {
        $teacher = $this->teacherFor($user);
        $classes = $this->classes($teacher);
        $classIds = $classes->pluck('id')->map(fn ($id): int => (int) $id)->values();
        $today = now()->toDateString();

        $todaySessions = $this->schedulesQuery($teacher)
            ->whereDate('date', $today)
            ->where('status', '!=', 'cancelled')
            ->with(['classRoom.formation', 'room', 'center'])
            ->orderBy('start_time')
            ->get();

        $upcoming = $this->schedulesQuery($teacher)
            ->where('date', '>=', $today)
            ->where('status', 'scheduled')
            ->with(['classRoom.formation', 'room', 'center'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->take(5)
            ->get();

        $evaluationsToCorrect = $this->evaluationsQuery($teacher)
            ->whereIn('status', ['draft', 'published'])
            ->with(['classRoom', 'formation'])
            ->withCount([
                'grades as graded_count' => fn (Builder $query) => $query->whereNotNull('grade'),
            ])
            ->with(['classRoom' => fn ($query) => $query->withCount([
                'enrollments as accepted_enrollments_count' => fn (Builder $enrollmentQuery) => $enrollmentQuery
                    ->where('status', Enrollment::STATUS_ACCEPTED),
            ])])
            ->latest('evaluation_date')
            ->take(20)
            ->get()
            ->filter(fn (Evaluation $evaluation): bool => $evaluation->status === 'draft'
                || $evaluation->graded_count < ($evaluation->classRoom?->accepted_enrollments_count ?? 0))
            ->take(5)
            ->values();

        $notifications = AppNotification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        $unreadNotificationsCount = AppNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return [
            'teacher' => $teacher,
            'center' => $teacher?->center ?? $user->center,
            'classes' => $classes,
            'classIds' => $classIds,
            'studentCount' => $this->studentCount($teacher),
            'domainLabels' => $this->domainLabels($teacher),
            'todaySessions' => $todaySessions,
            'upcoming' => $upcoming,
            'evaluationsToCorrect' => $evaluationsToCorrect,
            'notifications' => $notifications,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'finance' => $this->finance->teacherSituation($teacher),
        ];
    }
}
