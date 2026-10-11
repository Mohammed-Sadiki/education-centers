@extends('layouts.app')

@section('title', 'Nouvel utilisateur — ' . config('app.name'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}" class="text-decoration-none">Utilisateurs</a></li>
    <li class="breadcrumb-item active" aria-current="page">Nouveau</li>
@endsection

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <h4 class="fw-bold mb-0">
                        <i class="bi bi-person-plus-fill text-primary me-2"></i>Créer un utilisateur
                    </h4>
                    @if(auth()->user()->isCenterAdmin())
                        <p class="text-muted small mb-0 mt-1">
                            Centre : <strong>{{ auth()->user()->center?->name }}</strong> — le compte sera rattaché à votre centre.
                        </p>
                    @endif
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="{{ route('users.store') }}" id="form-create-user" novalidate>
                        @csrf

                        <div class="row g-3">

                            {{-- Nom --}}
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold small text-secondary">
                                    <i class="bi bi-person me-1"></i>Nom complet <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" placeholder="Jean Dupont" required autofocus>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Email --}}
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold small text-secondary">
                                    <i class="bi bi-envelope me-1"></i>Adresse e-mail <span class="text-danger">*</span>
                                </label>
                                <input type="email" id="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" placeholder="jean@education-centers.de" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Téléphone --}}
                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold small text-secondary">
                                    <i class="bi bi-telephone me-1"></i>Téléphone
                                </label>
                                <input type="text" id="phone" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone') }}" placeholder="+49 30 1234567">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Rôle --}}
                            <div class="col-md-6">
                                <label for="role" class="form-label fw-semibold small text-secondary">
                                    <i class="bi bi-shield me-1"></i>Rôle <span class="text-danger">*</span>
                                </label>
                                <select id="role" name="role"
                                        class="form-select @error('role') is-invalid @enderror" required>
                                    <option value="">— Sélectionner —</option>
                                    @foreach($allowedRoles as $value => $label)
                                        <option value="{{ $value }}" {{ old('role') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Centre (Super Admin uniquement) --}}
                            @if(auth()->user()->isSuperAdmin())
                                <div class="col-md-6" id="center-field">
                                    <label for="center_id" class="form-label fw-semibold small text-secondary">
                                        <i class="bi bi-building me-1"></i>Centre
                                        <span class="text-muted">(non requis pour Super Admin)</span>
                                    </label>
                                    <select id="center_id" name="center_id"
                                            class="form-select @error('center_id') is-invalid @enderror">
                                        <option value="">— Sans centre (Super Admin) —</option>
                                        @foreach($centers as $center)
                                            <option value="{{ $center->id }}" {{ old('center_id') == $center->id ? 'selected' : '' }}>
                                                {{ $center->name }} ({{ $center->city }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('center_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endif

                            {{-- Statut --}}
                            <div class="col-md-6">
                                <label for="status" class="form-label fw-semibold small text-secondary">
                                    <i class="bi bi-toggle-on me-1"></i>Statut <span class="text-danger">*</span>
                                </label>
                                <select id="status" name="status"
                                        class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="active"   {{ old('status', 'active') === 'active'   ? 'selected' : '' }}>Actif</option>
                                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                                </select>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12"><hr class="my-1"></div>

                            {{-- Mot de passe --}}
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold small text-secondary">
                                    <i class="bi bi-lock me-1"></i>Mot de passe <span class="text-danger">*</span>
                                </label>
                                <input type="password" id="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Min. 8 caractères" required autocomplete="new-password">
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Confirmation --}}
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label fw-semibold small text-secondary">
                                    <i class="bi bi-lock-fill me-1"></i>Confirmer le mot de passe <span class="text-danger">*</span>
                                </label>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                       class="form-control" placeholder="Répéter le mot de passe" required>
                            </div>

                        </div>

                        <div class="d-flex gap-2 mt-4 pt-2 border-top">
                            <button type="submit" class="btn btn-primary shadow-sm">
                                <i class="bi bi-person-check me-1"></i>Créer l'utilisateur
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Annuler
                            </a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
