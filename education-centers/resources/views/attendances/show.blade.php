@extends('layouts.app')
@section('title', 'Présence')

@section('content')
<x-ui.page-header title="Détail de présence" :description="$attendance->student?->full_name">
    <a href="{{ route('attendances.index') }}" class="ec-btn-secondary">Retour</a>
    @can('update', $attendance)
        <a href="{{ route('attendances.edit', $attendance) }}" class="ec-btn-primary">Modifier</a>
    @endcan
</x-ui.page-header>
<x-ui.card>
    <dl class="grid gap-3 text-sm sm:grid-cols-2">
        <div><dt class="text-slate-500">Étudiant</dt><dd class="font-medium">{{ $attendance->student?->full_name }}</dd></div>
        <div><dt class="text-slate-500">Classe</dt><dd class="font-medium">{{ $attendance->classRoom?->name }}</dd></div>
        <div><dt class="text-slate-500">Date</dt><dd>{{ optional($attendance->date)->format('d/m/Y') }}</dd></div>
        <div><dt class="text-slate-500">Statut</dt><dd><x-status-badge type="attendance" :status="$attendance->status" /></dd></div>
        <div><dt class="text-slate-500">Arrivée</dt><dd>{{ $attendance->arrival_time ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Retard (min)</dt><dd>{{ $attendance->late_minutes ?? '—' }}</dd></div>
        <div class="sm:col-span-2"><dt class="text-slate-500">Commentaire</dt><dd>{{ $attendance->comment ?: '—' }}</dd></div>
    </dl>
</x-ui.card>
@endsection
