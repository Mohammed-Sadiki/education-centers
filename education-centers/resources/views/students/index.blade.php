@extends('layouts.app')

@section('title', 'Dossiers Élèves — ' . config('app.name'))
@section('description', 'Gestion des dossiers administratifs des élèves')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Élèves</li>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- En-tête --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold mb-0">
                <i class="bi bi-mortarboard-fill text-primary me-2"></i>Dossiers Élèves
            </h1>
            <p class="text-muted small mb-0 mt-1">
                @if(auth()->user()->isSuperAdmin())
                    Tous les centres — {{ $students->total() }} dossier(s) élève(s) trouvé(s)
                @else
                    Centre : <strong>{{ auth()->user()->center?->name }}</strong> — {{ $students->total() }} dossier(s) trouvé(s)
                @endif
            </p>
        </div>
        @can('create', \App\Models\Student::class)
            <a href="{{ route('students.create') }}" class="btn btn-primary shadow-sm" id="btn-create-student">
                <i class="bi bi-person-plus-fill me-1"></i>Nouveau dossier élève
            </a>
        @endcan
    </div>

    {{-- Filtres --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('students.index') }}" id="form-filter-students">
                <div class="row g-3 align-items-end">
                    {{-- Numéro étudiant --}}
                    <div class="col-md-2">
                        <label for="student_number" class="form-label small fw-semibold text-secondary mb-1">
                            <i class="bi bi-hash me-1"></i>N° Étudiant
                        </label>
                        <input type="text" id="student_number" name="student_number" class="form-control"
                               placeholder="Ex: BER-000001" value="{{ request('student_number') }}">
                    </div>

                    {{-- Recherche nom / prénom --}}
                    <div class="col-md-3">
                        <label for="search" class="form-label small fw-semibold text-secondary mb-1">
                            <i class="bi bi-search me-1"></i>Nom ou prénom
                        </label>
                        <input type="text" id="search" name="search" class="form-control"
                               placeholder="Rechercher par nom..." value="{{ request('search') }}">
                    </div>

                    {{-- Filtre email --}}
                    <div class="col-md-2">
                        <label for="email" class="form-label small fw-semibold text-secondary mb-1">
                            <i class="bi bi-envelope me-1"></i>Email
                        </label>
                        <input type="text" id="email" name="email" class="form-control"
                               placeholder="email@domaine.de" value="{{ request('email') }}">
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
                        <div class="col-md-2">
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
                    <div class="col-md-1 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary w-100" title="Filtrer">
                            <i class="bi bi-funnel"></i>
                        </button>
                        @if(request()->hasAny(['student_number', 'search', 'email', 'status', 'center_id']))
                            <a href="{{ route('students.index') }}" class="btn btn-outline-secondary" title="Réinitialiser">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Liste des étudiants --}}
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">N° Étudiant</th>
                        <th>Nom & Prénom</th>
                        <th>Centre</th>
                        <th>Compte Utilisateur</th>
                        <th>Téléphone</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('students.show', $student) }}" class="fw-bold text-decoration-none font-monospace">
                                    {{ $student->student_number }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $student->full_name }}</div>
                                @if($student->date_of_birth)
                                    <small class="text-muted">
                                        Né(e) le {{ $student->date_of_birth->format('d/m/Y') }}
                                        @if($student->city) ({{ $student->city }}) @endif
                                    </small>
                                @endif
                            </td>
                            <td>
                                @if($student->center)
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-building me-1"></i>{{ $student->center->name }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($student->user)
                                    <div><i class="bi bi-person me-1 text-secondary"></i>{{ $student->user->name }}</div>
                                    <small class="text-muted">{{ $student->user->email }}</small>
                                @else
                                    <span class="text-danger small"><i class="bi bi-exclamation-triangle me-1"></i>Sans compte</span>
                                @endif
                            </td>
                            <td>
                                {{ $student->phone ?? '—' }}
                            </td>
                            <td>
                                @if($student->isActive())
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
                                    @can('view', $student)
                                        <a href="{{ route('students.show', $student) }}"
                                           class="btn btn-outline-secondary"
                                           title="Consulter le dossier"
                                           id="btn-show-student-{{ $student->id }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endcan

                                    {{-- Modifier --}}
                                    @can('update', $student)
                                        <a href="{{ route('students.edit', $student) }}"
                                           class="btn btn-outline-primary"
                                           title="Modifier le dossier"
                                           id="btn-edit-student-{{ $student->id }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcan

                                    {{-- Activer / Désactiver --}}
                                    @can('toggleStatus', $student)
                                        <form method="POST" action="{{ route('students.toggle-status', $student) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="btn btn-outline-{{ $student->isActive() ? 'warning' : 'success' }}"
                                                    title="{{ $student->isActive() ? 'Désactiver' : 'Activer' }}"
                                                    onclick="return confirm('Confirmer le changement de statut ?')"
                                                    id="btn-toggle-student-{{ $student->id }}">
                                                <i class="bi bi-toggle-{{ $student->isActive() ? 'on' : 'off' }}"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                                Aucun dossier élève trouvé.
                                @can('create', \App\Models\Student::class)
                                    <div class="mt-2">
                                        <a href="{{ route('students.create') }}" class="btn btn-sm btn-primary">
                                            Créer un dossier élève
                                        </a>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $students->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
