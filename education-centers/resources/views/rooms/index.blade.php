@extends('layouts.app')

@section('title', 'Salles de formation — ' . config('app.name'))
@section('description', 'Gestion des espaces et salles de cours des centres')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Salles</li>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- En-tête --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold mb-0">
                <i class="bi bi-door-open-fill text-primary me-2"></i>Gestion des Salles
            </h1>
            <p class="text-muted small mb-0 mt-1">
                @if(auth()->user()->isSuperAdmin())
                    Tous les centres — {{ $rooms->total() }} salle(s) trouvée(s)
                @else
                    Centre : <strong>{{ auth()->user()->center?->name }}</strong> — {{ $rooms->total() }} salle(s) trouvée(s)
                @endif
            </p>
        </div>
        @can('create', \App\Models\Room::class)
            <a href="{{ route('rooms.create') }}" class="btn btn-primary shadow-sm" id="btn-create-room">
                <i class="bi bi-plus-circle-fill me-1"></i>Nouvelle salle
            </a>
        @endcan
    </div>

    {{-- Filtres --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('rooms.index') }}" id="form-filter-rooms">
                <div class="row g-3 align-items-end">
                    {{-- Recherche par nom --}}
                    <div class="col-md-3">
                        <label for="search" class="form-label small fw-semibold text-secondary mb-1">
                            <i class="bi bi-search me-1"></i>Nom de la salle
                        </label>
                        <input type="text" id="search" name="search" class="form-control"
                               placeholder="Ex: Salle Informatique..." value="{{ request('search') }}">
                    </div>

                    {{-- Code salle --}}
                    <div class="col-md-2">
                        <label for="code" class="form-label small fw-semibold text-secondary mb-1">
                            <i class="bi bi-hash me-1"></i>Code
                        </label>
                        <input type="text" id="code" name="code" class="form-control"
                               placeholder="Ex: ROOM-01" value="{{ request('code') }}">
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
                            <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary" title="Réinitialiser">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Liste des salles --}}
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Code</th>
                        <th>Nom de la salle</th>
                        @if(auth()->user()->isSuperAdmin())
                            <th>Centre</th>
                        @endif
                        <th>Capacité</th>
                        <th>Bâtiment & Étage</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rooms as $room)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('rooms.show', $room) }}" class="fw-bold text-decoration-none font-monospace">
                                    {{ $room->code }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $room->name }}</div>
                                @if($room->description)
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;">
                                        {{ Str::limit($room->description, 50) }}
                                    </small>
                                @endif
                            </td>
                            @if(auth()->user()->isSuperAdmin())
                                <td>
                                    @if($room->center)
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-building me-1"></i>{{ $room->center->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                <span class="badge bg-info-subtle text-info border border-info-subtle">
                                    <i class="bi bi-people me-1"></i>{{ $room->capacity }} places
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">{{ $room->location }}</small>
                            </td>
                            <td>
                                @if($room->isActive())
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
                                    @can('view', $room)
                                        <a href="{{ route('rooms.show', $room) }}"
                                           class="btn btn-outline-secondary"
                                           title="Consulter"
                                           id="btn-show-room-{{ $room->id }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endcan

                                    {{-- Modifier --}}
                                    @can('update', $room)
                                        <a href="{{ route('rooms.edit', $room) }}"
                                           class="btn btn-outline-primary"
                                           title="Modifier"
                                           id="btn-edit-room-{{ $room->id }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcan

                                    {{-- Activer / Désactiver --}}
                                    @can('toggleStatus', $room)
                                        <form method="POST" action="{{ route('rooms.toggle-status', $room) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="btn btn-outline-{{ $room->isActive() ? 'warning' : 'success' }}"
                                                    title="{{ $room->isActive() ? 'Désactiver' : 'Activer' }}"
                                                    onclick="return confirm('Confirmer le changement de statut ?')"
                                                    id="btn-toggle-room-{{ $room->id }}">
                                                <i class="bi bi-toggle-{{ $room->isActive() ? 'on' : 'off' }}"></i>
                                            </button>
                                        </form>
                                    @endcan

                                    {{-- Supprimer --}}
                                    @can('delete', $room)
                                        <form method="POST" action="{{ route('rooms.destroy', $room) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-outline-danger"
                                                    title="Supprimer la salle"
                                                    onclick="return confirm('Confirmer la suppression de cette salle ?')"
                                                    id="btn-delete-room-{{ $room->id }}">
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
                                <i class="bi bi-door-closed fs-1 d-block mb-2 text-secondary"></i>
                                Aucune salle trouvée.
                                @can('create', \App\Models\Room::class)
                                    <div class="mt-2">
                                        <a href="{{ route('rooms.create') }}" class="btn btn-sm btn-primary">
                                            Créer une salle
                                        </a>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rooms->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $rooms->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
