@extends('layouts.app')

@section('title', 'Nouvelle Formation — ' . config('app.name'))
@section('description', 'Création d’un programme de formation')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('formations.index') }}">Formations</a></li>
    <li class="breadcrumb-item active" aria-current="page">Nouvelle formation</li>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="row justify-content-center">
        <div class="col-lg-9">

            {{-- En-tête --}}
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-0">
                        <i class="bi bi-journal-plus text-primary me-2"></i>Nouvelle Formation
                    </h1>
                    <p class="text-muted small mb-0 mt-1">
                        @if(auth()->user()->isCenterAdmin())
                            Centre : <strong>{{ auth()->user()->center?->name }}</strong>
                        @else
                            Ajouter un programme pédagogique au catalogue d'un centre.
                        @endif
                    </p>
                </div>
                <a href="{{ route('formations.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Retour
                </a>
            </div>

            <form method="POST" action="{{ route('formations.store') }}" id="form-create-formation">
                @csrf

                {{-- Bloc 1 : Identification du centre (Super Admin seulement) --}}
                @if(auth()->user()->isSuperAdmin())
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="card-title mb-0 fw-bold text-primary">
                                <i class="bi bi-building me-2"></i>Centre de rattachement
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="center_id" class="form-label fw-semibold">
                                        Centre éducatif <span class="text-danger">*</span>
                                    </label>
                                    <select id="center_id" name="center_id" class="form-select @error('center_id') is-invalid @enderror" required>
                                        <option value="">-- Sélectionner un centre --</option>
                                        @foreach($centers as $center)
                                            <option value="{{ $center->id }}" {{ old('center_id') == $center->id ? 'selected' : '' }}>
                                                {{ $center->name }} ({{ $center->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('center_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Le code formation doit être unique au sein du centre choisi.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Bloc 2 : Informations générales --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-info-circle me-2"></i>Informations générales
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label fw-semibold">
                                    Intitulé de la formation <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" placeholder="Ex: Deutsch A1 — Débutant" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="code" class="form-label fw-semibold">
                                    Code formation <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="code" name="code"
                                       class="form-control @error('code') is-invalid @enderror text-uppercase"
                                       value="{{ old('code') }}" placeholder="Ex: DE-A1" required>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Unique au sein du centre (ex: DE-A1).</div>
                            </div>

                            <div class="col-md-12">
                                <label for="description" class="form-label fw-semibold">Description du programme</label>
                                <textarea id="description" name="description" rows="4"
                                          class="form-control @error('description') is-invalid @enderror"
                                          placeholder="Objectifs pédagogiques, public visé, prérequis...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bloc 3 : Durée & Tarification --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-clock-history me-2"></i>Durée, Tarification & Statut
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="duration_hours" class="form-label fw-semibold">Volume horaire total (heures)</label>
                                <div class="input-group">
                                    <input type="number" id="duration_hours" name="duration_hours" min="0" step="1"
                                           class="form-control @error('duration_hours') is-invalid @enderror"
                                           value="{{ old('duration_hours') }}" placeholder="Ex: 120">
                                    <span class="input-group-text">heures</span>
                                </div>
                                @error('duration_hours')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="duration_weeks" class="form-label fw-semibold">Durée estimée (semaines)</label>
                                <div class="input-group">
                                    <input type="number" id="duration_weeks" name="duration_weeks" min="0" step="1"
                                           class="form-control @error('duration_weeks') is-invalid @enderror"
                                           value="{{ old('duration_weeks') }}" placeholder="Ex: 8">
                                    <span class="input-group-text">semaines</span>
                                </div>
                                @error('duration_weeks')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-5">
                                <label for="price" class="form-label fw-semibold">Prix / Tarif</label>
                                <input type="number" id="price" name="price" min="0" step="0.01"
                                       class="form-control @error('price') is-invalid @enderror"
                                       value="{{ old('price') }}" placeholder="Ex: 450.00">
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="currency" class="form-label fw-semibold">Devise</label>
                                <select id="currency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                    <option value="MAD" {{ old('currency', 'MAD') === 'MAD' ? 'selected' : '' }}>MAD (DH)</option>
                                    <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                                    <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD ($)</option>
                                    <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                                    <option value="CHF" {{ old('currency') === 'CHF' ? 'selected' : '' }}>CHF (CHF)</option>
                                </select>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="status" class="form-label fw-semibold">
                                    Statut <span class="text-danger">*</span>
                                </label>
                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="active"   {{ old('status', 'active') === 'active'   ? 'selected' : '' }}>Actif</option>
                                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Boutons d'action --}}
                <div class="d-flex justify-content-end gap-3 mb-5">
                    <a href="{{ route('formations.index') }}" class="btn btn-outline-secondary px-4">Annuler</a>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm" id="btn-submit-formation">
                        <i class="bi bi-check-circle-fill me-1"></i>Enregistrer la formation
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection
