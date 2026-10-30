<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTeacherRequest;
use App\Models\ClassRoom;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ClassTeacherController extends Controller
{
    /**
     * Affecter un professeur à une classe.
     */
    public function store(AssignTeacherRequest $request, ClassRoom $class): RedirectResponse
    {
        $teacher = Teacher::findOrFail($request->validated('teacher_id'));

        // Sécurité supplémentaire : vérification de centre
        if ($teacher->center_id !== $class->center_id) {
            abort(403, __('Le professeur et la classe n\'appartiennent pas au même centre.'));
        }

        $class->teachers()->syncWithoutDetaching([$teacher->id]);

        // Notification pédagogique : le professeur affecté (via son compte).
        if ($teacher->user) {
            app(\App\Services\NotificationService::class)->sendUnique(
                $teacher->user,
                'teacher_assignment',
                __('Nouvelle affectation de classe'),
                __('Vous avez été affecté(e) à la classe :code.', ['code' => $class->code]),
                route('teacher.classes'),
                'bi-academic',
                $class->center_id,
                \App\Models\ClassRoom::class,
                $class->id,
            );
        }

        return redirect()
            ->route('classes.show', $class)
            ->with('success', __('Le professeur :full_name (:teacher_number) a été affecté à la classe :code.', ['full_name' => $teacher->full_name, 'teacher_number' => $teacher->teacher_number, 'code' => $class->code]));
    }

    /**
     * Retirer un professeur d'une classe.
     */
    public function destroy(ClassRoom $class, Teacher $teacher): RedirectResponse
    {
        Gate::authorize('unassignTeacher', $class);

        // Sécurité multi-centres
        if ($teacher->center_id !== $class->center_id) {
            abort(403, __('Opération non autorisée.'));
        }

        // Détacher l'association sans supprimer le professeur
        $class->teachers()->detach($teacher->id);

        return redirect()
            ->route('classes.show', $class)
            ->with('success', __('Le professeur :full_name a été retiré de la classe :code.', ['full_name' => $teacher->full_name, 'code' => $class->code]));
    }
}
