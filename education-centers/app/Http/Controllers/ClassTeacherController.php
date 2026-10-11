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
            abort(403, "Le professeur et la classe n'appartiennent pas au même centre.");
        }

        $class->teachers()->syncWithoutDetaching([$teacher->id]);

        return redirect()
            ->route('classes.show', $class)
            ->with('success', "Le professeur {$teacher->full_name} ({$teacher->teacher_number}) a été affecté à la classe {$class->code}.");
    }

    /**
     * Retirer un professeur d'une classe.
     */
    public function destroy(ClassRoom $class, Teacher $teacher): RedirectResponse
    {
        Gate::authorize('unassignTeacher', $class);

        // Sécurité multi-centres
        if ($teacher->center_id !== $class->center_id) {
            abort(403, "Opération non autorisée.");
        }

        // Détacher l'association sans supprimer le professeur
        $class->teachers()->detach($teacher->id);

        return redirect()
            ->route('classes.show', $class)
            ->with('success', "Le professeur {$teacher->full_name} a été retiré de la classe {$class->code}.");
    }
}
