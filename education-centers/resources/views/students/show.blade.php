@extends('layouts.app')

@section('title', "Dossier Élève {$student->student_number} — " . config('app.name'))
@section('description', "Fiche administrative de l’élève {$student->full_name}")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Élèves</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $student->student_number }}</li>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- En-tête de la fiche --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 58px; height: 58px; font-size: 1.5rem;">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h1 class="h3 fw-bold mb-0">{{ $student->full_name }}</h1>
                    @if($student->isActive())
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
                    N° Étudiant : <strong>{{ $student->student_number }}</strong>
                    @if($student->center)
                        &bull; Centre : <strong>{{ $student->center->name }} ({{ $student->center->code }})</strong>
                    @endif
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
            @can('update', $student)
                <a href="{{ route('students.edit', $student) }}" class="btn btn-primary" id="btn-edit-student">
                    <i class="bi bi-pencil me-1"></i>Modifier
                </a>
            @endcan
            @can('toggleStatus', $student)
                <form method="POST" action="{{ route('students.toggle-status', $student) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="btn btn-outline-{{ $student->isActive() ? 'warning' : 'success' }}"
                            onclick="return confirm('Confirmer le changement de statut ?')"
                            id="btn-toggle-status">
                        <i class="bi bi-toggle-{{ $student->isActive() ? 'on' : 'off' }} me-1"></i>
                        {{ $student->isActive() ? 'Désactiver' : 'Activer' }}
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
                                <th class="w-40 ps-4 text-secondary small">Numéro étudiant</th>
                                <td class="font-monospace fw-bold text-primary">{{ $student->student_number }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Nom</th>
                                <td class="fw-semibold">{{ $student->last_name }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Prénom</th>
                                <td class="fw-semibold">{{ $student->first_name }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Date de naissance</th>
                                <td>
                                    {{ $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '—' }}
                                    @if($student->date_of_birth)
                                        <span class="text-muted small">({{ $student->date_of_birth->age }} ans)</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Lieu de naissance</th>
                                <td>{{ $student->place_of_birth ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Genre</th>
                                <td>
                                    @if($student->gender === 'male')
                                        <i class="bi bi-gender-male text-primary me-1"></i>Masculin
                                    @elseif($student->gender === 'female')
                                        <i class="bi bi-gender-female text-danger me-1"></i>Féminin
                                    @elseif($student->gender === 'other')
                                        <i class="bi bi-gender-ambiguous me-1"></i>Autre
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Nationalité</th>
                                <td>{{ $student->nationality ?? '—' }}</td>
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
                                    @if($student->phone)
                                        <a href="tel:{{ $student->phone }}" class="text-decoration-none">
                                            <i class="bi bi-telephone me-1"></i>{{ $student->phone }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Adresse postale</th>
                                <td>{{ $student->address ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Code postal</th>
                                <td>{{ $student->postal_code ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Ville</th>
                                <td>{{ $student->city ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Pays</th>
                                <td>{{ $student->country ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- Colonne droite : Contact d'urgence & Informations administratives --}}
        <div class="col-lg-6">

            {{-- Contact d'urgence --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-telephone-plus-fill me-2"></i>Contact d'urgence
                    </h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <tbody>
                            <tr>
                                <th class="w-40 ps-4 text-secondary small">Nom du contact</th>
                                <td class="fw-semibold">{{ $student->emergency_contact_name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Téléphone d'urgence</th>
                                <td>
                                    @if($student->emergency_contact_phone)
                                        <a href="tel:{{ $student->emergency_contact_phone }}" class="text-decoration-none text-danger fw-semibold">
                                            <i class="bi bi-telephone-fill me-1"></i>{{ $student->emergency_contact_phone }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Lien / Relation</th>
                                <td>{{ $student->emergency_contact_relation ?? '—' }}</td>
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
                                <th class="w-40 ps-4 text-secondary small">Centre de formation</th>
                                <td>
                                    @if($student->center)
                                        <span class="fw-semibold">{{ $student->center->name }}</span>
                                        <span class="badge bg-light text-dark border ms-1">{{ $student->center->code }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Compte de connexion</th>
                                <td>
                                    @if($student->user)
                                        <div class="fw-semibold">{{ $student->user->name }}</div>
                                        <div class="small text-muted">{{ $student->user->email }}</div>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle mt-1">
                                            Rôle : {{ $student->user->role }}
                                        </span>
                                    @else
                                        <span class="text-danger small">Aucun compte rattaché</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Statut administratif</th>
                                <td>
                                    @if($student->isActive())
                                        <span class="badge bg-success">Actif</span>
                                    @else
                                        <span class="badge bg-secondary">Inactif</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Date de création du dossier</th>
                                <td>{{ $student->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 text-secondary small">Dernière modification</th>
                                <td>{{ $student->updated_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Notes & Observations --}}
            @if($student->notes)
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-journal-text me-2"></i>Notes & Remarques
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-secondary" style="white-space: pre-line;">{{ $student->notes }}</p>
                    </div>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection
