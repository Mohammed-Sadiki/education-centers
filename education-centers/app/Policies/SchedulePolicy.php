<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    /**
     * Le Super Admin a tous les droits sur tous les centres.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Voir la liste des séances.
     */
    public function viewAny(User $user): bool
    {
        return $user->isCenterAdmin() || $user->isTeacher() || $user->isStudent();
    }

    /**
     * Voir le détail d'une séance.
     */
    public function view(User $user, Schedule $schedule): bool
    {
        if ($user->isStudent()) {
            if ((int) $user->center_id !== (int) $schedule->center_id || $user->student === null) {
                return false;
            }

            return Enrollment::query()
                ->where('student_id', $user->student->id)
                ->where('center_id', $user->center_id)
                ->where('class_id', $schedule->class_id)
                ->where('status', Enrollment::STATUS_ACCEPTED)
                ->exists();
        }

        if ($user->isCenterAdmin()) {
            return (int) $user->center_id === (int) $schedule->center_id;
        }

        if ($user->isTeacher()) {
            $teacher = $user->teacher;

            return $teacher !== null
                && (int) $schedule->center_id === (int) $teacher->center_id
                && (int) $schedule->teacher_id === (int) $teacher->id
                && $schedule->classRoom !== null
                && $schedule->classRoom->hasTeacher($teacher);
        }

        return false;
    }

    /**
     * Créer une séance.
     */
    public function create(User $user): bool
    {
        return $user->isCenterAdmin();
    }

    /**
     * Mettre à jour une séance.
     */
    public function update(User $user, Schedule $schedule): bool
    {
        return $user->isCenterAdmin() && $user->center_id === $schedule->center_id;
    }

    /**
     * Supprimer une séance.
     */
    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->isCenterAdmin() && $user->center_id === $schedule->center_id;
    }

    /**
     * Modifier le statut (annuler, terminer, etc.).
     */
    public function toggleStatus(User $user, Schedule $schedule): bool
    {
        return $user->isCenterAdmin() && $user->center_id === $schedule->center_id;
    }
}
