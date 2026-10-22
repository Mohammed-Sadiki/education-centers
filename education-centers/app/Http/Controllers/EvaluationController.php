<?php

namespace App\Http\Controllers;

use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Evaluation::class);
        $user = $request->user();
        $query = Evaluation::query()->with(['classRoom', 'formation', 'teacher.user']);
        if ($user->isCenterAdmin() || $user->isTeacher()) {
            $query->where('center_id', $user->center_id);
        } elseif ($user->isStudent()) {
            $classIds = Enrollment::where('student_id', $user->student?->id)->where('status', 'accepted')->pluck('class_id');
            $query->whereIn('class_id', $classIds)->where('center_id', $user->center_id);
        }
        if ($classId = $request->input('class_id')) {
            $query->where('class_id', $classId);
        }
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        $evaluations = $query->latest('evaluation_date')->paginate(15)->withQueryString();
        $centerId = $user->isSuperAdmin() ? null : $user->center_id;
        $classes = ClassRoom::when($centerId, fn ($q) => $q->where('center_id', $centerId))->orderBy('name')->get();
        $centers = $user->isSuperAdmin() ? Center::orderBy('name')->get() : collect();

        return view('evaluations.index', compact('evaluations', 'classes', 'centers'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Evaluation::class);
        $user = $request->user();
        $centerId = $user->isSuperAdmin() ? $request->input('center_id') : $user->center_id;
        $classes = ClassRoom::when($centerId, fn ($q) => $q->where('center_id', $centerId))->where('status', 'active')->orderBy('name')->get();
        $centers = $user->isSuperAdmin() ? Center::orderBy('name')->get() : collect();

        return view('evaluations.create', compact('classes', 'centers', 'centerId'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Evaluation::class);
        $user = $request->user();
        $centerId = $user->isSuperAdmin() ? (int) $request->input('center_id') : $user->center_id;
        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:exam,control,homework,oral,project,participation'],
            'evaluation_date' => ['required', 'date'],
            'max_grade' => ['required', 'numeric', 'min:1', 'max:100'],
            'coefficient' => ['required', 'numeric', 'min:0.1', 'max:10'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:draft,published,closed'],
        ]);
        $class = ClassRoom::findOrFail($data['class_id']);
        if ($class->center_id !== $centerId) {
            abort(403);
        }
        $data['center_id'] = $centerId;
        $data['formation_id'] = $class->formation_id;
        $data['teacher_id'] = $user->isTeacher() ? $user->teacher?->id : ($request->input('teacher_id') ?: null);
        $evaluation = Evaluation::create($data);

        return redirect()->route('evaluations.show', $evaluation)->with('success', __('Évaluation \\":title\\" créée.', ['title' => $evaluation->title]));
    }

    public function show(Evaluation $evaluation): View
    {
        Gate::authorize('view', $evaluation);
        $evaluation->load(['classRoom.formation', 'teacher.user', 'grades.student']);
        $enrolledStudentIds = Enrollment::where('class_id', $evaluation->class_id)->where('status', 'accepted')->pluck('student_id');
        $enrolledStudents = Student::whereIn('id', $enrolledStudentIds)->where('status', 'active')->orderBy('last_name')->get();
        $grades = $evaluation->grades()->with('student')->get()->keyBy('student_id');
        $average = $evaluation->grades()->whereNotNull('grade')->avg('grade');

        return view('evaluations.show', compact('evaluation', 'enrolledStudents', 'grades', 'average'));
    }

    public function edit(Evaluation $evaluation): View
    {
        Gate::authorize('update', $evaluation);
        $evaluation->load(['classRoom']);
        $classes = ClassRoom::where('center_id', $evaluation->center_id)->orderBy('name')->get();

        return view('evaluations.edit', compact('evaluation', 'classes'));
    }

    public function update(Request $request, Evaluation $evaluation): RedirectResponse
    {
        Gate::authorize('update', $evaluation);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:exam,control,homework,oral,project,participation'],
            'evaluation_date' => ['required', 'date'],
            'max_grade' => ['required', 'numeric', 'min:1', 'max:100'],
            'coefficient' => ['required', 'numeric', 'min:0.1', 'max:10'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:draft,published,closed'],
        ]);
        $evaluation->update($data);

        return redirect()->route('evaluations.show', $evaluation)->with('success', __('Évaluation mise à jour.'));
    }

    public function destroy(Evaluation $evaluation): RedirectResponse
    {
        Gate::authorize('delete', $evaluation);
        $evaluation->delete();

        return redirect()->route('evaluations.index')->with('success', __('Évaluation supprimée.'));
    }

    public function saveGrades(Request $request, Evaluation $evaluation): RedirectResponse
    {
        Gate::authorize('update', $evaluation);
        $request->validate([
            'grades' => ['required', 'array'],
            'grades.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'grades.*.grade' => ['nullable', 'numeric', 'min:0', 'max:'.$evaluation->max_grade],
            'grades.*.is_absent' => ['nullable', 'boolean'],
            'grades.*.comment' => ['nullable', 'string', 'max:500'],
        ]);
        $count = 0;
        foreach ($request->input('grades', []) as $g) {
            Grade::updateOrCreate(
                ['evaluation_id' => $evaluation->id, 'student_id' => $g['student_id']],
                [
                    'center_id' => $evaluation->center_id,
                    'enrollment_id' => Enrollment::where('student_id', $g['student_id'])->where('class_id', $evaluation->class_id)->where('status', 'accepted')->first()?->id,
                    'grade' => isset($g['is_absent']) && $g['is_absent'] ? null : ($g['grade'] ?? null),
                    'is_absent' => isset($g['is_absent']) && $g['is_absent'],
                    'comment' => $g['comment'] ?? null,
                    'graded_at' => now()->toDateString(),
                ]
            );
            $count++;
        }

        return redirect()->route('evaluations.show', $evaluation)->with('success', __(':count notes enregistrées.', ['count' => $count]));
    }
}
