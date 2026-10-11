@extends('layouts.app')
@section('title', 'Utilisateurs')

@section('content')
<x-ui.page-header title="Utilisateurs" :description="auth()->user()->isSuperAdmin() ? 'Tous les centres' : 'Centre : '.auth()->user()->center?->name">
    @can('create', \App\Models\User::class)
        <a href="{{ route('users.create') }}" class="ec-btn-primary" id="btn-create-user"><x-icon name="plus" class="h-4 w-4" /> Ajouter</a>
    @endcan
</x-ui.page-header>

<x-ui.card class="mb-4">
    <form method="GET" action="{{ route('users.index') }}" class="grid gap-3 md:grid-cols-4">
        <input type="text" name="search" value="{{ request('search') }}" class="ec-input" placeholder="Nom ou email">
        @if(auth()->user()->isSuperAdmin())
            <select name="center_id" class="ec-select">
                <option value="">Tous les centres</option>
                @foreach($centers as $center)
                    <option value="{{ $center->id }}" @selected(request('center_id') == $center->id)>{{ $center->name }}</option>
                @endforeach
            </select>
        @endif
        <select name="role" class="ec-select">
            <option value="">Tous les rôles</option>
            <option value="center_admin" @selected(request('role')==='center_admin')>Admin Centre</option>
            <option value="teacher" @selected(request('role')==='teacher')>Professeur</option>
            <option value="student" @selected(request('role')==='student')>Élève</option>
            @if(auth()->user()->isSuperAdmin())
                <option value="super_admin" @selected(request('role')==='super_admin')>Super Admin</option>
            @endif
        </select>
        <button class="ec-btn-secondary">Filtrer</button>
    </form>
</x-ui.card>

<div class="ec-table-wrap">
    <table class="ec-table">
        <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Centre</th><th>Statut</th><th></th></tr></thead>
        <tbody>
            @forelse($users as $u)
                <tr>
                    <td class="font-medium">{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td><x-role-label :role="$u->role" /></td>
                    <td>{{ $u->center?->name ?? 'Tous les centres' }}</td>
                    <td><x-status-badge :status="$u->status" /></td>
                    <td class="text-right"><a href="{{ route('users.show', $u) }}" class="ec-btn-ghost ec-btn-sm">Voir</a></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-ui.empty title="Aucun utilisateur" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($users->hasPages())<div class="mt-4">{{ $users->links() }}</div>@endif
@endsection
