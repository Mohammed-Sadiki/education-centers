@extends('layouts.app')

@section('title', 'Formations — ' . config('app.name'))
@section('description', 'Gestion des programmes de formation proposés par les centres')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Formations</li>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- En-tête --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold mb-0">
                <i class="bi bi-journal-bookmark-fill text-primary me-2"></i>Gestion des Formations
            </h1>
            <p class="text-muted small mb-0 mt-1">
                @if(auth()->user()->isSuperAdmin())
                    Tous les centres — {{ $formations->total() }} programme(s) de formation trouvé(s)
                @else
                    Centre : <strong>{{ auth()->user()->center?->name }}</strong> — {{ $formations->total() }} programme(s) de formation trouvé(s)
                @endif
            </p>
        </div>
        @can('create', \App\Models\Formation::class)
            <a href="{{ route('formations.create') }}" class="btn btn-primary shadow-sm" id="btn-create-formation">
                <i class="bi bi-plus-circle-fill me-1"></i>Nouvelle formation
            </a>
        @endcan
    </div>

    {{-- Filtres --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('formations.index') }}" id="form-filter-formations">
                <div class="row g-3 align-items-end">
                    {{-- Recherche par nom --}}
                    <div class="col-md-3">
                        <label for="search" class="form-label small fw-semibold text-secondary mb-1">
                            <i class="bi bi-search me-1"></i>Nom de formation
                        </label>
                        <input type="text" id="search" name="search" class="form-control"
                               placeholder="Ex: Deutsch A1, Python..." value="{{ request('search') }}">
                    </div>

                    {{-- Code formation --}}
                    <div class="col-md-2">
                        <label for="code" class="form-label small fw-semibold text-secondary mb-1">
                            <i class="bi bi-hash me-1"></i>Code
                        </label>
                        <input type="text" id="code" name="code" class="form-control"
                               placeholder="Ex: DE-A1" value="{{ request('code') }}">
                    </div>

                    {{-- Filtre statut --}}
                    <div class="col-md-2">
                        <label for="status" class="form-label small fw-semibold text-secondary mb-1">Statut</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Actif</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                        </select>
                    </div>

                    {{-- Filtre centre (Super Admin seulement) --}}
                    @if(auth()->user()->isSuperAdmin())
                        <div class="col-md-3">
                            <label for="center_id" class="form-label small fw-semibold text-secondary mb-1">
                                <i class="bi bi-building me-1"></i>Centre
                            </label>
                            <select id="center_id" name="center_id" class="form-select">
                                <option value="">Tous les centres</option>
                                @foreach($centers as $center)
                                    <option value="{{ $center->id }}" {{ request('center_id') == $center->id ? 'selected' : '' }}>
                                        {{ $center->name }} ({{ $center->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Actions filtre --}}
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary w-100" title="Filtrer">
                            <i class="bi bi-funnel me-1"></i>Filtrer
                        </button>
                        @if(request()->hasAny(['search', 'code', 'status', 'center_id']))
                            <a href="{{ route('formations.index') }}" class="btn btn-outline-secondary" title="Réinitialiser">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Liste des formations --}}
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Code</th>
                        <th>Intitulé de la formation</th>
                        @if(auth()->user()->isSuperAdmin())
                            <th>Centre</th>
                        @endif
                        <th>Durée</th>
                        <th>Tarif</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($formations as $formation)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('formations.show', $formation) }}" class="fw-bold text-decoration-none font-monospace">
                                    {{ $formation->code }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $formation->name }}</div>
                                @if($formation->description)
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 280px;">
                                        {{ Str::limit($formation->description, 60) }}
                                    </small>
                                @endif
                            </td>
                            @if(auth()->user()->isSuperAdmin())
                                <td>
                                    @if($formation->center)
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-building me-1"></i>{{ $formation->center->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                {{ $formation->formatted_duration }}
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $formation->formatted_price }}</span>
                            </td>
                            <td>
                                @if($formation->isActive())
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i>Actif
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                        <i class="bi bi-pause-circle-fill me-1"></i>Inactif
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm" role="group">
                                    {{-- Consulter --}}
                                    @can('view', $formation)
                                        <a href="{{ route('formations.show', $formation) }}"
                                           class="btn btn-outline-secondary"
                                           title="Consulter"
                                           id="btn-show-formation-{{ $formation->id }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endcan

                                    {{-- Modifier --}}
                                    @can('update', $formation)
                                        <a href="{{ route('formations.edit', $formation) }}"
                                           class="btn btn-outline-primary"
                                           title="Modifier"
                                           id="btn-edit-formation-{{ $formation->id }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcan

                                    {{-- Activer / Désactiver --}}
                                    @can('toggleStatus', $formation)
                                        <form method="POST" action="{{ route('formations.toggle-status', $formation) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="btn btn-outline-{{ $formation->isActive() ? 'warning' : 'success' }}"
                                                    title="{{ $formation->isActive() ? 'Désactiver' : 'Activer' }}"
                                                    onclick="return confirm('Confirmer le changement de statut ?')"
                                                    id="btn-toggle-formation-{{ $formation->id }}">
                                                <i class="bi bi-toggle-{{ $formation->isActive() ? 'on' : 'off' }}"></i>
                                            </button>
                                        </form>
                                    @endcan

                                    {{-- Supprimer --}}
                                    @can('delete', $formation)
                                        <form method="POST" action="{{ route('formations.destroy', $formation) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-outline-danger"
                                                    title="Supprimer la formation"
                                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette formation ? Cette action est irréversible.')"
                                                    id="btn-delete-formation-{{ $formation->id }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isSuperAdmin() ? '7' : '6' }}" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                                Aucune formation trouvée.
                                @can('create', \App\Models\Formation::class)
                                    <div class="mt-2">
                                        <a href="{{ route('formations.create') }}" class="btn btn-sm btn-primary">
                                            Créer une formation
                                        </a>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($formations->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $formations->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
