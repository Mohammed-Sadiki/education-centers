@extends('layouts.app')

@section('title', $user->name . ' — Utilisateur')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}" class="text-decoration-none">Utilisateurs</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $user->name }}</li>
@endsection

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="fw-bold mb-0">
                        <i class="bi bi-person-badge text-primary me-2"></i>{{ $user->name }}
                    </h4>
                    <div class="d-flex gap-2 flex-wrap">
                        @can('update', $user)
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil me-1"></i>Modifier
                            </a>
                        @endcan
                        @can('toggleStatus', $user)
                            <form action="{{ route('users.toggle-status', $user) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="btn btn-sm {{ $user->isActive() ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                        onclick="return confirm('{{ $user->isActive() ? 'Désactiver' : 'Réactiver' }} cet utilisateur ?')">
                                    <i class="bi bi-{{ $user->isActive() ? 'pause-circle' : 'play-circle' }} me-1"></i>
                                    {{ $user->isActive() ? 'Désactiver' : 'Réactiver' }}
                                </button>
                            </form>
                        @endcan
                        @can('delete', $user)
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Supprimer définitivement « {{ addslashes($user->name) }} » ? Cette action est irréversible.')">
                                    <i class="bi bi-trash me-1"></i>Supprimer
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-4">
                        {{-- Colonne gauche --}}
                        <div class="col-md-8">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                    <span class="text-muted fw-semibold">Nom complet</span>
                                    <span class="fw-bold">{{ $user->name }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                    <span class="text-muted fw-semibold">Adresse e-mail</span>
                                    <span>{{ $user->email }}</span>
                                </li>
                                @if($user->phone)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                        <span class="text-muted fw-semibold">Téléphone</span>
                                        <span>{{ $user->phone }}</span>
                                    </li>
                                @endif
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                    <span class="text-muted fw-semibold">Rôle</span>
                                    <div>
                                        @switch($user->role)
                                            @case('super_admin')
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6 px-3 py-2">
                                                    <i class="bi bi-shield-shaded me-1"></i>Super Admin
                                                </span>
                                                @break
                                            @case('center_admin')
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-2">
                                                    <i class="bi bi-briefcase me-1"></i>Admin Centre
                                                </span>
                                                @break
                                            @case('teacher')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 px-3 py-2">
                                                    <i class="bi bi-mortarboard me-1"></i>Professeur
                                                </span>
                                                @break
                                            @case('student')
                                                <span class="badge bg-info-subtle text-info border border-info-subtle fs-6 px-3 py-2">
                                                    <i class="bi bi-book me-1"></i>Élève
                                                </span>
                                                @break
                                        @endswitch
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                    <span class="text-muted fw-semibold">Centre</span>
                                    <span>
                                        @if($user->isSuperAdmin())
                                            <span class="badge bg-dark px-3 py-2">
                                                <i class="bi bi-globe-europe-africa me-1"></i>Tous les centres
                                            </span>
                                        @elseif($user->center)
                                            <a href="{{ route('centers.show', $user->center) }}" class="text-decoration-none">
                                                {{ $user->center->name }} ({{ $user->center->city }})
                                            </a>
                                        @else
                                            <span class="text-danger">Non rattaché</span>
                                        @endif
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                    <span class="text-muted fw-semibold">Statut</span>
                                    @if($user->isActive())
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                            <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Actif
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2">
                                            <i class="bi bi-circle me-1" style="font-size:.5rem;"></i>Inactif
                                        </span>
                                    @endif
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                    <span class="text-muted fw-semibold">Créé le</span>
                                    <span>{{ $user->created_at->format('d/m/Y à H:i') }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                    <span class="text-muted fw-semibold">Modifié le</span>
                                    <span>{{ $user->updated_at->format('d/m/Y à H:i') }}</span>
                                </li>
                            </ul>
                        </div>

                        {{-- Colonne droite --}}
                        <div class="col-md-4">
                            <div class="d-flex flex-column align-items-center p-4 bg-light rounded-3">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold mb-3"
                                     style="width:80px;height:80px;font-size:2rem;">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div class="fw-bold text-center">{{ $user->name }}</div>
                                <div class="text-muted small text-center">{{ $user->email }}</div>
                                <div class="mt-2">
                                    @if($user->isActive())
                                        <span class="badge bg-success">Compte actif</span>
                                    @else
                                        <span class="badge bg-secondary">Compte inactif</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Retour à la liste
                    </a>
                    @can('update', $user)
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-primary btn-sm shadow-sm">
                            <i class="bi bi-pencil me-1"></i>Modifier cet utilisateur
                        </a>
                    @endcan
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
