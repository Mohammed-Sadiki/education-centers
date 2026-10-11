@extends('layouts.app')
@section('title', 'Évaluations')

@section('content')
<x-ui.page-header title="Évaluations & notes" description="Contrôles, examens et moyennes.">
    @can('create', \App\Models\Evaluation::class)
        <a href="{{ route('evaluations.create') }}" class="ec-btn-primary"><x-icon name="plus" class="h-4 w-4" /> Nouvelle évaluation</a>
    @endcan
</x-ui.page-header>
<x-ui.card class="mb-4">
    <form method="GET" class="grid gap-3 md:grid-cols-3">
        <select name="class_id" class="ec-select">
            <option value="">Toutes les classes</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected(request('class_id')==$class->id)>{{ $class->name }}</option>
            @endforeach
        </select>
        <select name="type" class="ec-select">
            <option value="">Tous les types</option>
            @foreach(['exam'=>'Examen','control'=>'Contrôle','homework'=>'Devoir','oral'=>'Oral','project'=>'Projet','participation'=>'Participation'] as $k=>$v)
                <option value="{{ $k }}" @selected(request('type')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <button class="ec-btn-secondary">Filtrer</button>
    </form>
</x-ui.card>
<div class="ec-table-wrap">
    <table class="ec-table">
        <thead><tr><th>Titre</th><th>Classe</th><th>Type</th><th>Date</th><th>Statut</th><th></th></tr></thead>
        <tbody>
            @forelse($evaluations as $evaluation)
                <tr>
                    <td class="font-medium">{{ $evaluation->title }}</td>
                    <td>{{ $evaluation->classRoom?->name }}</td>
                    <td>{{ $evaluation->type_label }}</td>
                    <td>{{ optional($evaluation->evaluation_date)->format('d/m/Y') }}</td>
                    <td><x-status-badge type="evaluation" :status="$evaluation->status" /></td>
                    <td class="text-right">
                        @unless(auth()->user()->isStudent())
                            <a href="{{ route('evaluations.show', $evaluation) }}" class="ec-btn-ghost ec-btn-sm">Notes</a>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-ui.empty title="Aucune évaluation" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($evaluations->hasPages())<div class="mt-4">{{ $evaluations->links() }}</div>@endif
@endsection
