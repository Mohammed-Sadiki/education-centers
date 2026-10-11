@extends('layouts.app')
@section('title', 'Gestion des inscriptions')

@section('content')
<x-ui.page-header title="Inscriptions des élèves" description="Cycle de candidature et affectation aux classes.">
    <a href="{{ route('enrollments.create') }}" class="ec-btn-primary"><x-icon name="plus" class="h-4 w-4" /> Nouvelle inscription</a>
</x-ui.page-header>
<x-ui.card class="mb-4">
    <form method="GET" action="{{ route('enrollments.index') }}" class="grid gap-3 md:grid-cols-6">
        @if(auth()->user()->isSuperAdmin())
            <select name="center_id" class="ec-select">
                <option value="">Tous les centres</option>
                @foreach($centers as $c)
                    <option value="{{ $c->id }}" @selected(request('center_id')==$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        @endif
        <input type="text" name="search" class="ec-input" placeholder="N° ou élève" value="{{ request('search') }}">
        <select name="formation_id" class="ec-select">
            <option value="">Toutes les formations</option>
            @foreach($formations as $f)
                <option value="{{ $f->id }}" @selected(request('formation_id')==$f->id)>{{ $f->name }}</option>
            @endforeach
        </select>
        <select name="class_id" class="ec-select">
            <option value="">Toutes les classes</option>
            @foreach($classes as $cl)
                <option value="{{ $cl->id }}" @selected(request('class_id')==$cl->id)>{{ $cl->name }}</option>
            @endforeach
        </select>
        <select name="status" class="ec-select">
            <option value="">Tous les statuts</option>
            <option value="pending" @selected(request('status')==='pending')>En attente</option>
            <option value="under_review" @selected(request('status')==='under_review')>En examen</option>
            <option value="accepted" @selected(request('status')==='accepted')>Acceptée</option>
            <option value="rejected" @selected(request('status')==='rejected')>Refusée</option>
            <option value="cancelled" @selected(request('status')==='cancelled')>Annulée</option>
        </select>
        <button class="ec-btn-secondary">Filtrer</button>
    </form>
</x-ui.card>
<div class="ec-table-wrap">
    <table class="ec-table">
        <thead>
            <tr>
                <th>N°</th>
                @if(auth()->user()->isSuperAdmin())<th>Centre</th>@endif
                <th>Élève</th>
                <th>Formation</th>
                <th>Classe</th>
                <th>Date</th>
                <th>Statut</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($enrollments as $e)
                <tr>
                    <td class="font-mono text-xs"><a href="{{ route('enrollments.show', $e) }}" class="text-brand-700 hover:underline">{{ $e->registration_number }}</a></td>
                    @if(auth()->user()->isSuperAdmin())<td>{{ $e->center->name }}</td>@endif
                    <td>{{ $e->student->full_name }}</td>
                    <td>{{ $e->formation->name }}</td>
                    <td>{{ $e->classRoom?->name ?? 'Non affectée' }}</td>
                    <td>{{ $e->formatted_application_date }}</td>
                    <td><x-status-badge type="enrollment" :status="$e->status" /></td>
                    <td class="text-right"><a href="{{ route('enrollments.show', $e) }}" class="ec-btn-ghost ec-btn-sm">Voir</a></td>
                </tr>
            @empty
                <tr><td colspan="8"><x-ui.empty title="Aucune inscription" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($enrollments->hasPages())<div class="mt-4">{{ $enrollments->links() }}</div>@endif
@endsection
