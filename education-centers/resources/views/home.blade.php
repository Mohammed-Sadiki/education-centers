@extends('layouts.app')

@section('title', 'Accueil — Education Centers')
@section('description', 'Plateforme multi-centres de gestion des centres d\'éducation en Allemagne')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center py-5">
            <i class="bi bi-building-fill-check display-1 text-primary mb-4"></i>
            <h1 class="display-5 fw-bold mb-3">Education Centers</h1>
            <p class="lead text-muted mb-4">
                Plateforme multi-centres de gestion des centres d'éducation en Allemagne.
            </p>
            <div class="alert alert-success mb-4">
                <i class="bi bi-shield-check me-2"></i>
                <strong>Étape 3 opérationnelle :</strong> Authentification, rôles &amp; permissions multi-centres.
            </div>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                @guest
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Espace de Connexion
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg shadow-sm">
                        <i class="bi bi-speedometer2 me-2"></i>Accéder au Tableau de bord
                    </a>
                    @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('centers.index') }}" class="btn btn-outline-primary btn-lg shadow-sm">
                            <i class="bi bi-buildings me-2"></i>Gérer les centres
                        </a>
                    @endif
                @endguest
            </div>
        </div>
    </div>
</div>
@endsection
