@extends('layouts.app')

@section('title', 'Utilisateurs — ' . config('app.name'))
@section('description', 'Gestion des comptes utilisateurs de la plateforme')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Utilisateurs</li>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- En-tête --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold mb-0">
                <i class="bi bi-people-fill text-primary me-2"></i>Gestion des Utilisateurs
            </h1>
            <p class="text-muted small mb-0 mt-1">
                @if(auth()->user()->isSuperAdmin())
                    Tous les centres — {{ $users->total() }} utilisateur(s) trouvé(s)
                @else
                    Centre : <strong>{{ auth()->user()->center?->name }}</strong> — {{ $users->total() }} utilisateur(s)
                @endif
            </p>
        </div>
        @can('create', \App\Models\User::class)
            <a href="{{ route('users.create') }}" class="btn btn-primary shadow-sm" id="btn-create-user">
                <i class="bi bi-person-plus-fill me-1"></i>Nouvel utilisateur
            </a>
        @endcan
    </div>

    {{-- Filtres --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('users.index') }}" id="form-filter-users">
                <div class="row g-3 align-items-end">
                    {{-- Recherche --}}
                    <div class="col-md-4">
                        <label for="search" class="form-label small fw-semibold text-secondary mb-1">
                            <i class="bi bi-search me-1"></i>Recherche
                        </label>
                        <input type="text" id="search" name="search" class="form-control"
                               placeholder="Nom ou e-mail…" value="{{ request('search') }}">
                    </div>

                    {{-- Filtre rôle --}}
                    <div class="col-md-2">
                        <label for="role" class="form-label small fw-semibold text-secondary mb-1">Rôle</label>
                        <select id="role" name="role" class="form-select">
                            <option value="">Tous les rôles</option>
                            <option value="super_admin"  {{ request('role') === 'super_admin'  ? 'selected' : '' }}>Super Admin</option>
                            <option value="center_admin" {{ request('role') === 'center_admin' ? 'selected' : '' }}>Admin Centre</option>
                            <option value="teacher"      {{ request('role') === 'teacher'      ? 'selected' : '' }}>Professeur</option>
                            <option value="student"      {{ request('role') === 'student'      ? 'selected' : '' }}>Élève</option>
                        </select>
                    </div>

                    {{-- Filtre statut --}}
                    <div class="col-md-2">
                        <label for="status" class="form-label small fw-semibold text-secondary mb-1">Statut</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">Tous</option>
                            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Actif</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                        </select>
                    </div>

                    {{-- Filtre centre (Super Admin uniquement) --}}
                    @if(auth()->user()->isSuperAdmin() && $centers->isNotEmpty())
                        <div class="col-md-2">
                            <label for="center_id" class="form-label small fw-semibold text-secondary mb-1">Centre</label>
                            <select id="center_id" name="center_id" class="form-select">
                                <option value="">Tous les centres</option>
                                <option value="null" {{ request('center_id') === 'null' ? 'selected' : '' }}>Sans centre (Super Admin)</option>
                                @foreach($centers as $center)
                                    <option value="{{ $center->id }}" {{ request('center_id') == $center->id ? 'selected' : '' }}>
                                        {{ $center->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Boutons --}}
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-funnel me-1"></i>Filtrer
                        </button>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tableau --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            @if($users->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-people display-4 d-block mb-3 opacity-25"></i>
                    <p class="mb-0">Aucun utilisateur trouvé pour ces critères.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Nom</th>
                                <th>E-mail</th>
                                <th>Rôle</th>
                                <th>Centre</th>
                                <th>Statut</th>
                                <th>Créé le</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold"
                                                 style="width:36px;height:36px;font-size:.85rem;flex-shrink:0;">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <span class="fw-semibold text-dark">{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-muted small">{{ $user->email }}</td>
                                    <td>
                                        @switch($user->role)
                                            @case('super_admin')
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                    <i class="bi bi-shield-shaded me-1"></i>Super Admin
                                                </span>
                                                @break
                                            @case('center_admin')
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                    <i class="bi bi-briefcase me-1"></i>Admin Centre
                                                </span>
                                                @break
                                            @case('teacher')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="bi bi-mortarboard me-1"></i>Professeur
                                                </span>
                                                @break
                                            @case('student')
                                                <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                    <i class="bi bi-book me-1"></i>Élève
                                                </span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="text-muted small">
                                        {{ $user->center?->name ?? '—' }}
                                    </td>
                                    <td>
                                        @if($user->isActive())
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Actif
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                                <i class="bi bi-circle me-1" style="font-size:.5rem;"></i>Inactif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $user->created_at->format('d/m/Y') }}</td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-1">
                                            @can('view', $user)
                                                <a href="{{ route('users.show', $user) }}"
                                                   class="btn btn-sm btn-outline-secondary" title="Voir">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            @endcan
                                            @can('update', $user)
                                                <a href="{{ route('users.edit', $user) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            @endcan
                                            @can('toggleStatus', $user)
                                                <form action="{{ route('users.toggle-status', $user) }}" method="POST" class="d-inline">
                                                    @csrf @method('PATCH')
                                                    <button type="submit"
                                                            class="btn btn-sm {{ $user->isActive() ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                            title="{{ $user->isActive() ? 'Désactiver' : 'Réactiver' }}"
                                                            onclick="return confirm('{{ $user->isActive() ? 'Désactiver' : 'Réactiver' }} « {{ addslashes($user->name) }} » ?')">
                                                        <i class="bi bi-{{ $user->isActive() ? 'pause-circle' : 'play-circle' }}"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                            @can('delete', $user)
                                                <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"
                                                            onclick="return confirm('Supprimer définitivement « {{ addslashes($user->name) }} » ? Cette action est irréversible.')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($users->hasPages())
                    <div class="px-4 py-3 border-top">
                        {{ $users->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

</div>
@endsection
