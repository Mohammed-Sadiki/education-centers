@extends('layouts.app')

@section('title', "Modifier le dossier professeur {$teacher->teacher_number} — " . config('app.name'))
@section('description', "Modification du dossier administratif de {$teacher->full_name}")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('teachers.index') }}">Professeurs</a></li>
    <li class="breadcrumb-item"><a href="{{ route('teachers.show', $teacher) }}">{{ $teacher->teacher_number }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Modifier</li>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="row justify-content-center">
        <div class="col-lg-10">

            {{-- En-tête --}}
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-0">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Modifier le Dossier Professeur
                    </h1>
                    <p class="text-muted small mb-0 mt-1">
                        N° Professeur : <strong class="font-monospace">{{ $teacher->teacher_number }}</strong>
                        &bull; Centre : <strong>{{ $teacher->center?->name }}</strong>
                        &bull; Compte : <strong>{{ $teacher->user?->email }}</strong>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('teachers.show', $teacher) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Retour
                    </a>
                </div>
            </div>

            <form method="POST" action="{{ route('teachers.update', $teacher) }}" id="form-edit-teacher">
                @csrf
                @method('PUT')

                {{-- Bloc 1 : Informations personnelles --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-person-lines-fill me-2"></i>1. Informations personnelles
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label fw-semibold">
                                    Prénom <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="first_name" name="first_name"
                                       class="form-control @error('first_name') is-invalid @enderror"
                                       value="{{ old('first_name', $teacher->first_name) }}" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="last_name" class="form-label fw-semibold">
                                    Nom <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="last_name" name="last_name"
                                       class="form-control @error('last_name') is-invalid @enderror"
                                       value="{{ old('last_name', $teacher->last_name) }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="date_of_birth" class="form-label fw-semibold">Date de naissance</label>
                                <input type="date" id="date_of_birth" name="date_of_birth"
                                       class="form-control @error('date_of_birth') is-invalid @enderror"
                                       value="{{ old('date_of_birth', optional($teacher->date_of_birth)->format('Y-m-d')) }}">
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="place_of_birth" class="form-label fw-semibold">Lieu de naissance</label>
                                <input type="text" id="place_of_birth" name="place_of_birth"
                                       class="form-control @error('place_of_birth') is-invalid @enderror"
                                       value="{{ old('place_of_birth', $teacher->place_of_birth) }}" placeholder="Ex: Munich">
                                @error('place_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="gender" class="form-label fw-semibold">Genre</label>
                                <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror">
                                    <option value="">Non précisé</option>
                                    <option value="male"   {{ old('gender', $teacher->gender) === 'male'   ? 'selected' : '' }}>Masculin</option>
                                    <option value="female" {{ old('gender', $teacher->gender) === 'female' ? 'selected' : '' }}>Féminin</option>
                                    <option value="other"  {{ old('gender', $teacher->gender) === 'other'  ? 'selected' : '' }}>Autre</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="nationality" class="form-label fw-semibold">Nationalité</label>
                                <input type="text" id="nationality" name="nationality"
                                       class="form-control @error('nationality') is-invalid @enderror"
                                       value="{{ old('nationality', $teacher->nationality) }}">
                                @error('nationality')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold">Numéro de téléphone</label>
                                <input type="text" id="phone" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $teacher->phone) }}" placeholder="+49 30 9876543">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bloc 2 : Informations professionnelles --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-briefcase me-2"></i>2. Informations professionnelles
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="specialization" class="form-label fw-semibold">Spécialisation / Discipline</label>
                                <input type="text" id="specialization" name="specialization"
                                       class="form-control @error('specialization') is-invalid @enderror"
                                       value="{{ old('specialization', $teacher->specialization) }}" placeholder="Ex: Mathématiques, Informatique">
                                @error('specialization')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="qualification" class="form-label fw-semibold">Diplôme / Qualification</label>
                                <input type="text" id="qualification" name="qualification"
                                       class="form-control @error('qualification') is-invalid @enderror"
                                       value="{{ old('qualification', $teacher->qualification) }}" placeholder="Ex: Master en Pédagogie">
                                @error('qualification')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="hire_date" class="form-label fw-semibold">Date d'embauche</label>
                                <input type="date" id="hire_date" name="hire_date"
                                       class="form-control @error('hire_date') is-invalid @enderror"
                                       value="{{ old('hire_date', optional($teacher->hire_date)->format('Y-m-d')) }}">
                                @error('hire_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bloc 3 : Coordonnées postales & Statut --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-geo-alt me-2"></i>3. Coordonnées & Statut
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="address" class="form-label fw-semibold">Adresse postale</label>
                                <input type="text" id="address" name="address"
                                       class="form-control @error('address') is-invalid @enderror"
                                       value="{{ old('address', $teacher->address) }}" placeholder="Ex: Unter den Linden 10">
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="postal_code" class="form-label fw-semibold">Code postal</label>
                                <input type="text" id="postal_code" name="postal_code"
                                       class="form-control @error('postal_code') is-invalid @enderror"
                                       value="{{ old('postal_code', $teacher->postal_code) }}" placeholder="Ex: 10117">
                                @error('postal_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="city" class="form-label fw-semibold">Ville</label>
                                <input type="text" id="city" name="city"
                                       class="form-control @error('city') is-invalid @enderror"
                                       value="{{ old('city', $teacher->city) }}" placeholder="Ex: Berlin">
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="country" class="form-label fw-semibold">Pays</label>
                                <input type="text" id="country" name="country"
                                       class="form-control @error('country') is-invalid @enderror"
                                       value="{{ old('country', $teacher->country) }}">
                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label for="notes" class="form-label fw-semibold">Notes & Observations administratives</label>
                                <textarea id="notes" name="notes" rows="3"
                                          class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $teacher->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="status" class="form-label fw-semibold">
                                    Statut du dossier <span class="text-danger">*</span>
                                </label>
                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="active"   {{ old('status', $teacher->status) === 'active'   ? 'selected' : '' }}>Actif</option>
                                    <option value="inactive" {{ old('status', $teacher->status) === 'inactive' ? 'selected' : '' }}>Inactif</option>
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
                    <a href="{{ route('teachers.show', $teacher) }}" class="btn btn-outline-secondary px-4">Annuler</a>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm" id="btn-update-teacher">
                        <i class="bi bi-save me-1"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection
