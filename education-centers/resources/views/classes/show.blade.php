@extends('layouts.app')

@section('title', "Classe {$class->code} — " . config('app.name'))
@section('description', "Fiche descriptive de la classe {$class->name}")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">Classes</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $class->code }}</li>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- En-tête de la fiche --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 58px; height: 58px; font-size: 1.5rem;">
                <i class="bi bi-diagram-3-fill"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h1 class="h3 fw-bold mb-0">{{ $class->name }}</h1>
                    @if($class->isActive())
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            <i class="bi bi-check-circle-fill me-1"></i>Actif
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                            <i class="bi bi-pause-circle-fill me-1"></i>Inactif
                        </span>
                    @endif
                </div>
                <div class="text-muted small mt-1 font-monospace">
                    Code : <strong class="text-primary">{{ $class->code }}</strong>
                    @if($class->formation)
                        &bull; Formation : <strong>{{ $class->formation->name }} ({{ $class->formation->code }})</strong>
                    @endif
                    @if($class->center)
                        &bull; Centre : <strong>{{ $class->center->name }} ({{ $class->center->code }})</strong>
                    @endif
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('classes.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
            @can('update', $class)
                <a href="{{ route('classes.edit', $class) }}" class="btn btn-primary" id="btn-edit-class">
                    <i class="bi bi-pencil me-1"></i>Modifier
                </a>
            @endcan
            @can('toggleStatus', $class)
                <form method="POST" action="{{ route('classes.toggle-status', $class) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="btn btn-outline-{{ $class->isActive() ? 'warning' : 'success' }}"
                            onclick="return confirm('Confirmer le changement de statut ?')"
                            id="btn-toggle-status">
                        <i class="bi bi-toggle-{{ $class->isActive() ? 'on' : 'off' }} me-1"></i>
                        {{ $class->isActive() ? 'Désactiver' : 'Activer' }}
                    </button>
                </form>
            @endcan
            @can('delete', $class)
                <form method="POST" action="{{ route('classes.destroy', $class) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="btn btn-outline-danger"
                            onclick="return confirm('Confirmer la suppression de cette classe ?')"
                            id="btn-delete-class">
                        <i class="bi bi-trash me-1"></i>Supprimer
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        {{-- Colonne gauche : Informations Générales & Pédagogiques --}}
        <div class="col-lg-7">

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-info-circle me-2"></i>Informations de la classe
                    </h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <tbody>
                            <tr>
                                <th class="w-35 ps-4 text-secondary small">Code de la classe</th>
                                <td class="font-monospace fw-bold text-primary fs-6">{{ $class->code }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Intitulé</th>
                                <td class="fw-semibold">{{ $class->name }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Formation rattachée</th>
                                <td>
                                    @if($class->formation)
                                        <a href="{{ route('formations.show', $class->formation) }}" class="fw-semibold text-decoration-none">
                                            {{ $class->formation->name }}
                                        </a>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">
                                            {{ $class->formation->code }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Centre de formation</th>
                                <td>
                                    @if($class->center)
                                        <span class="fw-semibold">{{ $class->center->name }}</span>
                                        <span class="badge bg-light text-dark border ms-1">{{ $class->center->code }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Statut</th>
                                <td>
                                    @if($class->isActive())
                                        <span class="badge bg-success">Actif</span>
                                    @else
                                        <span class="badge bg-secondary">Inactif</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Description --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-journal-text me-2"></i>Description & Remarques
                    </h5>
                </div>
                <div class="card-body">
                    @if($class->description)
                        <p class="mb-0 text-secondary" style="white-space: pre-line;">{{ $class->description }}</p>
                    @else
                        <span class="text-muted fst-italic">Aucune description renseignée pour cette classe.</span>
                    @endif
                </div>
            </div>

        </div>

        {{-- Colonne droite : Capacité, Période & Administration --}}
        <div class="col-lg-5">

            {{-- Capacité & Période --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-calendar-event me-2"></i>Capacité & Période
                    </h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <tbody>
                            <tr>
                                <th class="w-45 ps-4 text-secondary small">Capacité d’accueil</th>
                                <td class="fw-semibold">
                                    {{ $class->capacity ? $class->capacity . ' places' : 'Illimitée' }}
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Date de début</th>
                                <td>
                                    {{ $class->start_date ? $class->start_date->format('d/m/Y') : '—' }}
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Date de fin</th>
                                <td>
                                    {{ $class->end_date ? $class->end_date->format('d/m/Y') : '—' }}
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Période globale</th>
                                <td>{{ $class->formatted_period }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Administration & Horodatage --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-shield-check me-2"></i>Informations administratives
                    </h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <tbody>
                            <tr>
                                <th class="w-45 ps-4 text-secondary small">Date de création</th>
                                <td>{{ $class->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Dernière modification</th>
                                <td>{{ $class->updated_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
