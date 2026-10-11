@extends('layouts.app')

@section('title', 'Nouvelle Classe — ' . config('app.name'))
@section('description', 'Création d’une classe pédagogique')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">Classes</a></li>
    <li class="breadcrumb-item active" aria-current="page">Nouvelle classe</li>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="row justify-content-center">
        <div class="col-lg-9">

            {{-- En-tête --}}
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-0">
                        <i class="bi bi-diagram-3-fill text-primary me-2"></i>Nouvelle Classe
                    </h1>
                    <p class="text-muted small mb-0 mt-1">
                        @if(auth()->user()->isCenterAdmin())
                            Centre : <strong>{{ auth()->user()->center?->name }}</strong>
                        @else
                            Rattacher un groupe classe à une formation et un centre.
                        @endif
                    </p>
                </div>
                <a href="{{ route('classes.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Retour
                </a>
            </div>

            <form method="POST" action="{{ route('classes.store') }}" id="form-create-class">
                @csrf

                {{-- Bloc 1 : Rattachement Centre & Formation --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-diagram-2 me-2"></i>1. Rattachement pédagogique
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            {{-- Sélection du centre pour Super Admin --}}
                            @if(auth()->user()->isSuperAdmin())
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
                                    <div class="form-text">Seules les formations de ce centre seront utilisables.</div>
                                </div>
                            @endif

                            {{-- Sélection de la formation --}}
                            <div class="col-md-{{ auth()->user()->isSuperAdmin() ? '6' : '12' }}">
                                <label for="formation_id" class="form-label fw-semibold">
                                    Formation rattachée <span class="text-danger">*</span>
                                </label>
                                <select id="formation_id" name="formation_id" class="form-select @error('formation_id') is-invalid @enderror" required>
                                    <option value="">-- Sélectionner une formation --</option>
                                    @foreach($formations as $formation)
                                        <option value="{{ $formation->id }}"
                                                data-center-id="{{ $formation->center_id }}"
                                                {{ old('formation_id') == $formation->id ? 'selected' : '' }}>
                                            {{ $formation->code }} — {{ $formation->name }}
                                            @if(auth()->user()->isSuperAdmin() && $formation->center)
                                                [{{ $formation->center->name }}]
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('formation_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bloc 2 : Informations de la classe --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-info-circle me-2"></i>2. Informations générales
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label fw-semibold">
                                    Intitulé de la classe <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" placeholder="Ex: Groupe A1 — Matin" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="code" class="form-label fw-semibold">
                                    Code classe <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="code" name="code"
                                       class="form-control @error('code') is-invalid @enderror text-uppercase"
                                       value="{{ old('code') }}" placeholder="Ex: A1-01" required>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Unique au sein de ce centre.</div>
                            </div>

                            <div class="col-md-12">
                                <label for="description" class="form-label fw-semibold">Description / Remarques</label>
                                <textarea id="description" name="description" rows="3"
                                          class="form-control @error('description') is-invalid @enderror"
                                          placeholder="Horaires indicatifs, rythme de cours...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bloc 3 : Capacité & Calendrier --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-calendar-event me-2"></i>3. Capacité & Période
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="capacity" class="form-label fw-semibold">Capacité maximale (élèves)</label>
                                <div class="input-group">
                                    <input type="number" id="capacity" name="capacity" min="1" step="1"
                                           class="form-control @error('capacity') is-invalid @enderror"
                                           value="{{ old('capacity', 20) }}" placeholder="Ex: 20">
                                    <span class="input-group-text">places</span>
                                </div>
                                @error('capacity')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="start_date" class="form-label fw-semibold">Date de début</label>
                                <input type="date" id="start_date" name="start_date"
                                       class="form-control @error('start_date') is-invalid @enderror"
                                       value="{{ old('start_date') }}">
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="end_date" class="form-label fw-semibold">Date de fin</label>
                                <input type="date" id="end_date" name="end_date"
                                       class="form-control @error('end_date') is-invalid @enderror"
                                       value="{{ old('end_date') }}">
                                @error('end_date')
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

                {{-- Actions --}}
                <div class="d-flex justify-content-end gap-3 mb-5">
                    <a href="{{ route('classes.index') }}" class="btn btn-outline-secondary px-4">Annuler</a>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm" id="btn-submit-class">
                        <i class="bi bi-check-circle-fill me-1"></i>Enregistrer la classe
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>

@if(auth()->user()->isSuperAdmin())
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const centerSelect = document.getElementById('center_id');
        const formationSelect = document.getElementById('formation_id');

        function filterFormations() {
            const selectedCenter = centerSelect.value;
            for (let i = 0; i < formationSelect.options.length; i++) {
                const opt = formationSelect.options[i];
                if (!opt.value) continue;
                const optCenter = opt.getAttribute('data-center-id');
                if (!selectedCenter || optCenter === selectedCenter) {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                    if (opt.selected) {
                        formationSelect.value = '';
                    }
                }
            }
        }

        centerSelect.addEventListener('change', filterFormations);
        if (centerSelect.value) {
            filterFormations();
        }
    });
</script>
@endpush
@endif
@endsection
