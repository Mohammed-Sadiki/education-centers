@extends('layouts.app')

@section('title', "Dossier Professeur {$teacher->teacher_number} — " . config('app.name'))
@section('description', "Fiche administrative de l’enseignant {$teacher->full_name}")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('teachers.index') }}">Professeurs</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $teacher->teacher_number }}</li>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- En-tête de la fiche --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 58px; height: 58px; font-size: 1.5rem;">
                <i class="bi bi-person-workspace"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h1 class="h3 fw-bold mb-0">{{ $teacher->full_name }}</h1>
                    @if($teacher->isActive())
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
                    N° Professeur : <strong>{{ $teacher->teacher_number }}</strong>
                    @if($teacher->center)
                        &bull; Centre : <strong>{{ $teacher->center->name }} ({{ $teacher->center->code }})</strong>
                    @endif
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('teachers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
            @can('update', $teacher)
                <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-primary" id="btn-edit-teacher">
                    <i class="bi bi-pencil me-1"></i>Modifier
                </a>
            @endcan
            @can('toggleStatus', $teacher)
                <form method="POST" action="{{ route('teachers.toggle-status', $teacher) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="btn btn-outline-{{ $teacher->isActive() ? 'warning' : 'success' }}"
                            onclick="return confirm('Confirmer le changement de statut ?')"
                            id="btn-toggle-status">
                        <i class="bi bi-toggle-{{ $teacher->isActive() ? 'on' : 'off' }} me-1"></i>
                        {{ $teacher->isActive() ? 'Désactiver' : 'Activer' }}
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        {{-- Colonne gauche : Infos Personnelles & Coordonnées --}}
        <div class="col-lg-6">

            {{-- Informations personnelles --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-person-lines-fill me-2"></i>Informations personnelles
                    </h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <tbody>
                            <tr>
                                <th class="w-40 ps-4 text-secondary small">Numéro professeur</th>
                                <td class="font-monospace fw-bold text-primary">{{ $teacher->teacher_number }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Nom</th>
                                <td class="fw-semibold">{{ $teacher->last_name }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Prénom</th>
                                <td class="fw-semibold">{{ $teacher->first_name }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Date de naissance</th>
                                <td>
                                    {{ $teacher->date_of_birth ? $teacher->date_of_birth->format('d/m/Y') : '—' }}
                                    @if($teacher->date_of_birth)
                                        <span class="text-muted small">({{ $teacher->date_of_birth->age }} ans)</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Lieu de naissance</th>
                                <td>{{ $teacher->place_of_birth ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Genre</th>
                                <td>
                                    @if($teacher->gender === 'male')
                                        <i class="bi bi-gender-male text-primary me-1"></i>Masculin
                                    @elseif($teacher->gender === 'female')
                                        <i class="bi bi-gender-female text-danger me-1"></i>Féminin
                                    @elseif($teacher->gender === 'other')
                                        <i class="bi bi-gender-ambiguous me-1"></i>Autre
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Nationalité</th>
                                <td>{{ $teacher->nationality ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Coordonnées --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-geo-alt-fill me-2"></i>Coordonnées
                    </h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <tbody>
                            <tr>
                                <th class="w-40 ps-4 text-secondary small">Téléphone</th>
                                <td>
                                    @if($teacher->phone)
                                        <a href="tel:{{ $teacher->phone }}" class="text-decoration-none">
                                            <i class="bi bi-telephone me-1"></i>{{ $teacher->phone }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Adresse postale</th>
                                <td>{{ $teacher->address ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Code postal</th>
                                <td>{{ $teacher->postal_code ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Ville</th>
                                <td>{{ $teacher->city ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Pays</th>
                                <td>{{ $teacher->country ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- Colonne droite : Profil Professionnel & Informations administratives --}}
        <div class="col-lg-6">

            {{-- Profil Professionnel --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-briefcase-fill me-2"></i>Informations professionnelles
                    </h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <tbody>
                            <tr>
                                <th class="w-40 ps-4 text-secondary small">Discipline / Spécialisation</th>
                                <td>
                                    @if($teacher->specialization)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                            {{ $teacher->specialization }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Diplôme / Qualification</th>
                                <td class="fw-semibold">{{ $teacher->qualification ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Date d'embauche</th>
                                <td>
                                    {{ $teacher->hire_date ? $teacher->hire_date->format('d/m/Y') : '—' }}
                                    @if($teacher->hire_date)
                                        <span class="text-muted small">({{ $teacher->hire_date->diffForHumans() }})</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Informations administratives --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-building-fill me-2"></i>Informations administratives
                    </h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <tbody>
                            <tr>
                                <th class="w-40 ps-4 text-secondary small">Centre de rattachement</th>
                                <td>
                                    @if($teacher->center)
                                        <span class="fw-semibold">{{ $teacher->center->name }}</span>
                                        <span class="badge bg-light text-dark border ms-1">{{ $teacher->center->code }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Compte de connexion</th>
                                <td>
                                    @if($teacher->user)
                                        <div class="fw-semibold">{{ $teacher->user->name }}</div>
                                        <div class="small text-muted">{{ $teacher->user->email }}</div>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle mt-1">
                                            Rôle : {{ $teacher->user->role }}
                                        </span>
                                    @else
                                        <span class="text-danger small">Aucun compte rattaché</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Statut administratif</th>
                                <td>
                                    @if($teacher->isActive())
                                        <span class="badge bg-success">Actif</span>
                                    @else
                                        <span class="badge bg-secondary">Inactif</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Date de création du dossier</th>
                                <td>{{ $teacher->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Dernière modification</th>
                                <td>{{ $teacher->updated_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Notes & Observations --}}
            @if($teacher->notes)
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-journal-text me-2"></i>Notes & Remarques
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-secondary" style="white-space: pre-line;">{{ $teacher->notes }}</p>
                    </div>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection
