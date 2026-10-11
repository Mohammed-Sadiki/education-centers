@extends('layouts.app')

@section('title', 'Emplois du temps & Planning')

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- En-tête --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Emplois du temps</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold mb-0 text-gray-800">
                <i class="bi bi-calendar-week text-primary me-2"></i>Planning des séances
            </h1>
            <p class="text-muted small mb-0">Gestion centralisée des cours, examens et ateliers par centre.</p>
        </div>
        <div>
            <a href="{{ route('schedules.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-calendar-plus"></i>
                <span>Programmer une séance</span>
            </a>
        </div>
    </div>

    {{-- Alertes --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filtres --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('schedules.index') }}" class="row g-2 align-items-end">
                @if(auth()->user()->isSuperAdmin())
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Centre</label>
                        <select name="center_id" class="form-select form-select-sm">
                            <option value="">Tous les centres</option>
                            @foreach($centers as $c)
                                <option value="{{ $c->id }}" {{ request('center_id') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Classe</label>
                    <select name="class_id" class="form-select form-select-sm">
                        <option value="">Toutes les classes</option>
                        @foreach($classes as $cl)
                            <option value="{{ $cl->id }}" {{ request('class_id') == $cl->id ? 'selected' : '' }}>
                                {{ $cl->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Professeur</label>
                    <select name="teacher_id" class="form-select form-select-sm">
                        <option value="">Tous les profs</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Salle</label>
                    <select name="room_id" class="form-select form-select-sm">
                        <option value="">Toutes les salles</option>
                        @foreach($rooms as $r)
                            <option value="{{ $r->id }}" {{ request('room_id') == $r->id ? 'selected' : '' }}>
                                {{ $r->name }} ({{ $r->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-1">
                    <label class="form-label small text-muted mb-1">Statut</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Programmée</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Terminée</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulée</option>
                    </select>
                </div>

                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100" title="Filtrer">
                        <i class="bi bi-funnel"></i>
                    </button>
                    @if(request()->hasAny(['center_id', 'date', 'class_id', 'teacher_id', 'room_id', 'status']))
                        <a href="{{ route('schedules.index') }}" class="btn btn-outline-secondary btn-sm" title="Réinitialiser">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Tableau des séances --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Date & Horaire</th>
                        @if(auth()->user()->isSuperAdmin())
                            <th>Centre</th>
                        @endif
                        <th>Classe & Formation</th>
                        <th>Professeur</th>
                        <th>Salle</th>
                        <th>Type / Titre</th>
                        <th>Statut</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $s)
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold text-dark">{{ $s->formatted_date }}</div>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-clock me-1 text-primary"></i>{{ $s->formatted_time }}
                                </span>
                            </td>

                            @if(auth()->user()->isSuperAdmin())
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        <i class="bi bi-geo-alt me-1"></i>{{ $s->center->name }}
                                    </span>
                                </td>
                            @endif

                            <td>
                                <div class="fw-semibold text-primary">{{ $s->classRoom->name }}</div>
                                <small class="text-muted">{{ $s->classRoom->formation->name ?? '—' }}</small>
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-person-badge text-muted"></i>
                                    <span>{{ $s->teacher->full_name }}</span>
                                </div>
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-info-subtle text-info-emphasis border">
                                        <i class="bi bi-door-closed me-1"></i>{{ $s->room->code }}
                                    </span>
                                    <small class="text-muted text-truncate" style="max-width: 120px;" title="{{ $s->room->name }}">
                                        {{ $s->room->name }}
                                    </small>
                                </div>
                            </td>

                            <td>
                                <span class="badge bg-light text-dark border me-1">{{ $s->type_label }}</span>
                                @if($s->title)
                                    <small class="text-muted d-block text-truncate" style="max-width: 140px;" title="{{ $s->title }}">
                                        {{ $s->title }}
                                    </small>
                                @endif
                            </td>

                            <td>
                                @if($s->status === 'scheduled')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-calendar-check me-1"></i>Programmée
                                    </span>
                                @elseif($s->status === 'completed')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-check2-all me-1"></i>Terminée
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                        <i class="bi bi-x-circle me-1"></i>Annulée
                                    </span>
                                @endif
                            </td>

                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('schedules.show', $s) }}" class="btn btn-outline-primary" title="Consulter">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('schedules.edit', $s) }}" class="btn btn-outline-secondary" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" 
                                            data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $s->id }}" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>

                                {{-- Modal Suppression --}}
                                <div class="modal fade" id="deleteModal-{{ $s->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content text-start">
                                            <div class="modal-header">
                                                <h5 class="modal-title fs-6 fw-bold">Confirmer la suppression</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                Êtes-vous sûr de vouloir supprimer définitivement cette séance du 
                                                <strong>{{ $s->formatted_date }}</strong> ({{ $s->formatted_time }}) 
                                                pour la classe <strong>{{ $s->classRoom->name }}</strong> ?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                                                <form action="{{ route('schedules.destroy', $s) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">Supprimer définitivement</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isSuperAdmin() ? 8 : 7 }}" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Aucune séance trouvée avec les critères sélectionnés.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($schedules->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $schedules->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
