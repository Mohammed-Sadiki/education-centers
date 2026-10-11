@extends('layouts.app')

@section('title', 'Connexion — ' . config('app.name', 'Education Centers'))
@section('description', 'Connexion sécurisée à la plateforme des centres d\'éducation')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white border-0 text-center pt-4 pb-2">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-shield-lock-fill fs-3"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">Connexion</h4>
                    <p class="text-muted small mb-0">Accédez à votre espace sécurisé</p>
                </div>

                <div class="card-body p-4">
                    {{-- Affichage des erreurs globales --}}
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                            <div>
                                <ul class="mb-0 ps-3 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" novalidate>
                        @csrf

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small text-secondary">
                                <i class="bi bi-envelope me-1"></i>Adresse e-mail
                            </label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   placeholder="nom@education-centers.de"
                                   required
                                   autocomplete="email"
                                   autofocus>
                            @error('email')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Mot de passe --}}
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold small text-secondary">
                                <i class="bi bi-lock me-1"></i>Mot de passe
                            </label>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   placeholder="••••••••"
                                   required
                                   autocomplete="current-password">
                            @error('password')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Se souvenir de moi --}}
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label small text-secondary" for="remember">
                                Se souvenir de moi
                            </label>
                        </div>

                        {{-- Bouton de connexion --}}
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Se connecter
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card-footer bg-light border-0 text-center py-3 rounded-bottom-3">
                    <span class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i>Authentification réservée au personnel et aux étudiants autorisés.
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
