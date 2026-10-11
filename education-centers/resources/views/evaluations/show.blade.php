@extends('layouts.app')
@section('title', $evaluation->title)

@section('content')
<x-ui.page-header title="{{ $evaluation->title }}" :description="$evaluation->classRoom?->name.' · '.$evaluation->type_label">
    @can('update', $evaluation)
        <a href="{{ route('evaluations.edit', $evaluation) }}" class="ec-btn-secondary">Modifier</a>
    @endcan
    <a href="{{ route('evaluations.index') }}" class="ec-btn-ghost">Retour</a>
</x-ui.page-header>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <x-ui.stat label="Note max" :value="$evaluation->max_grade" icon="chart" />
    <x-ui.stat label="Coefficient" :value="$evaluation->coefficient" icon="chart" tone="info" />
    <x-ui.stat label="Moyenne" :value="$average !== null ? number_format($average, 2, ',', ' ') : '—'" icon="chart" tone="success" />
</div>

@can('update', $evaluation)
<form method="POST" action="{{ route('evaluations.grades.save', $evaluation) }}" class="ec-card overflow-hidden p-0">
    @csrf
    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
        <h2 class="font-semibold">Saisie des notes</h2>
    </div>
    <div class="ec-table-wrap border-0">
        <table class="ec-table">
            <thead><tr><th>Étudiant</th><th>Note / {{ $evaluation->max_grade }}</th><th>Absent</th><th>Commentaire</th></tr></thead>
            <tbody>
                @forelse($enrolledStudents as $i => $student)
                    @php $g = $grades[$student->id] ?? null; @endphp
                    <tr>
                        <td class="font-medium">
                            {{ $student->full_name }}
                            <input type="hidden" name="grades[{{ $i }}][student_id]" value="{{ $student->id }}">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="{{ $evaluation->max_grade }}"
                                   name="grades[{{ $i }}][grade]" class="ec-input w-28"
                                   value="{{ old('grades.'.$i.'.grade', $g?->grade) }}">
                        </td>
                        <td>
                            <input type="checkbox" name="grades[{{ $i }}][is_absent]" value="1" @checked(old('grades.'.$i.'.is_absent', $g?->is_absent))>
                        </td>
                        <td>
                            <input name="grades[{{ $i }}][comment]" class="ec-input" value="{{ old('grades.'.$i.'.comment', $g?->comment) }}">
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-ui.empty title="Aucun étudiant inscrit dans cette classe" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($enrolledStudents->isNotEmpty())
        <div class="p-4"><button class="ec-btn-primary">Enregistrer les notes</button></div>
    @endif
</form>
@else
<div class="ec-table-wrap">
    <table class="ec-table">
        <thead><tr><th>Étudiant</th><th>Note</th></tr></thead>
        <tbody>
            @foreach($enrolledStudents as $student)
                @php $g = $grades[$student->id] ?? null; @endphp
                <tr>
                    <td>{{ $student->full_name }}</td>
                    <td>{{ $g?->is_absent ? 'Absent' : ($g?->grade !== null ? $g->grade.'/'.$evaluation->max_grade : '—') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endcan
@endsection
