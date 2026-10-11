@extends('layouts.app')
@section('title', 'Présences')

@section('content')
<x-ui.page-header title="Présences" description="Suivi des présences par séance et par classe.">
    @can('create', \App\Models\Attendance::class)
        <a href="{{ route('attendances.create') }}" class="ec-btn-primary"><x-icon name="plus" class="h-4 w-4" /> Saisir une séance</a>
    @endcan
</x-ui.page-header>

<x-ui.card class="mb-4">
    <form method="GET" class="grid gap-3 md:grid-cols-4">
        <select name="class_id" class="ec-select">
            <option value="">Toutes les classes</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>
            @endforeach
        </select>
        <select name="status" class="ec-select">
            <option value="">Tous les statuts</option>
            <option value="present" @selected(request('status')==='present')>Présent</option>
            <option value="absent" @selected(request('status')==='absent')>Absent</option>
            <option value="late" @selected(request('status')==='late')>Retard</option>
        </select>
        <input type="date" name="date" value="{{ request('date') }}" class="ec-input">
        <button class="ec-btn-secondary">Filtrer</button>
    </form>
</x-ui.card>

<div class="ec-table-wrap">
    <table class="ec-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Étudiant</th>
                <th>Classe</th>
                <th>Statut</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $attendance)
                <tr>
                    <td>{{ optional($attendance->date)->format('d/m/Y') }}</td>
                    <td class="font-medium">{{ $attendance->student?->full_name }}</td>
                    <td>{{ $attendance->classRoom?->name }}</td>
                    <td><x-status-badge type="attendance" :status="$attendance->status" /></td>
                    <td class="text-right"><a href="{{ route('attendances.show', $attendance) }}" class="ec-btn-ghost ec-btn-sm">Voir</a></td>
                </tr>
            @empty
                <tr><td colspan="5"><x-ui.empty title="Aucune présence" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($attendances->hasPages())
    <div class="mt-4">{{ $attendances->links() }}</div>
@endif
@endsection
