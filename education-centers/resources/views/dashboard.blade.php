@extends('layouts.app')

@section('title', 'Tableau de bord — ' . config('app.name', 'Education Centers'))
@section('description', 'Tableau de bord temporaire de vérification des permissions')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Tableau de bord</li>
@endsection

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            {{-- En-tête de bienvenue --}}
            <div class="p-4 mb-4 bg-white rounded-3 shadow-sm border">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h2 class="h3 fw-bold text-dark mb-1">
                            Bienvenue, {{ $user->name }} !
                        </h2>
                        <p class="text-muted mb-0 small">
                            Tableau de bord de vérification de session &bull; Étape 3 (Authentification & Rôles)
                        </p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        @if($user->isSuperAdmin())
                            <a href="{{ route('centers.index') }}" class="btn btn-outline-primary shadow-sm">
                                <i class="bi bi-buildings me-1"></i>Gestion des centres
                            </a>
                        @endif
                        @if($user->isSuperAdmin() || $user->isCenterAdmin())
                            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary shadow-sm">
                                <i class="bi bi-people me-1"></i>Gestion des utilisateurs
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Carte des informations utilisateur & permissions --}}
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-person-badge-fill text-primary"></i>Profil Utilisateur
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                    <span class="text-muted fw-semibold">Nom complet :</span>
                                    <span class="fw-bold text-dark">{{ $user->name }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                    <span class="text-muted fw-semibold">Adresse e-mail :</span>
                                    <span class="fw-bold text-dark">{{ $user->email }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                    <span class="text-muted fw-semibold">Rôle attribué :</span>
                                    <div>
                                        @switch($user->role)
                                            @case('super_admin')
                                                <span class="badge bg-danger fs-6 px-3 py-2">
                                                    <i class="bi bi-shield-shaded me-1"></i>Super Admin
                                                </span>
                                                @break
                                            @case('center_admin')
                                                <span class="badge bg-primary fs-6 px-3 py-2">
                                                    <i class="bi bi-briefcase-fill me-1"></i>Admin Centre
                                                </span>
                                                @break
                                            @case('teacher')
                                                <span class="badge bg-success fs-6 px-3 py-2">
                                                    <i class="bi bi-mortarboard-fill me-1"></i>Professeur
                                                </span>
                                                @break
                                            @case('student')
                                                <span class="badge bg-info text-dark fs-6 px-3 py-2">
                                                    <i class="bi bi-book-fill me-1"></i>Élève
                                                </span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary fs-6 px-3 py-2">{{ $user->role }}</span>
                                        @endswitch
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                    <span class="text-muted fw-semibold">Centre associé :</span>
                                    <div>
                                        @if($user->isSuperAdmin())
                                            <span class="badge bg-dark fs-6 px-3 py-2">
                                                <i class="bi bi-globe-europe-africa me-1"></i>Tous les centres
                                            </span>
                                        @elseif($user->center)
                                            <span class="badge bg-secondary fs-6 px-3 py-2">
                                                <i class="bi bi-geo-alt-fill me-1"></i>{{ $user->center->name }} ({{ $user->center->city }})
                                            </span>
                                        @else
                                            <span class="text-danger fw-semibold">Aucun centre rattaché</span>
                                        @endif
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Carte de synthèse des permissions effectives --}}
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-shield-check text-success"></i>Permissions & Périmètre d'Accès
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="fw-semibold text-secondary py-3">Périmètre des données</td>
                                            <td class="text-end py-3">
                                                @if($user->isSuperAdmin())
                                                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1">Global (Allemagne entière)</span>
                                                @else
                                                    <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1">Restreint au centre {{ $user->center?->name }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-secondary py-3">CRUD Centres (/centers)</td>
                                            <td class="text-end py-3">
                                                @if($user->isSuperAdmin())
                                                    <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Autorisé (Gestion complète)</span>
                                                @else
                                                    <span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i>Interdit (403 Forbidden)</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-secondary py-3">Statut de la session</td>
                                            <td class="text-end py-3">
                                                <span class="badge bg-success">Active & Authentifiée</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="alert alert-info mt-4 mb-0 small">
                                <i class="bi bi-info-circle-fill me-1"></i>
                                <strong>Note Étape 3 :</strong> Ce tableau de bord temporaire valide la configuration de vos rôles et l'isolation logique multi-centres. Il sera complété dans les étapes suivantes avec les statistiques, cours et inscriptions.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
