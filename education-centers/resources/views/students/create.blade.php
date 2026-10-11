@extends('layouts.app')

@section('title', 'Nouveau dossier élève — ' . config('app.name'))
@section('description', 'Création d’un dossier administratif étudiant')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Élèves</a></li>
    <li class="breadcrumb-item active" aria-current="page">Nouveau dossier</li>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="row justify-content-center">
        <div class="col-lg-10">

            {{-- En-tête --}}
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-0">
                        <i class="bi bi-person-plus-fill text-primary me-2"></i>Nouveau Dossier Élève
                    </h1>
                    <p class="text-muted small mb-0 mt-1">
                        Associez un compte utilisateur étudiant à un dossier administratif.
                    </p>
                </div>
                <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Retour
                </a>
            </div>

            @if($availableUsers->isEmpty())
                <div class="alert alert-warning border-0 shadow-sm mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-warning"></i>
                        <div>
                            <h5 class="alert-heading mb-1 fw-bold">Aucun compte élève disponible</h5>
                            <p class="mb-0 small">
                                Tous les comptes ayant le rôle <em>Élève (student)</em> possèdent déjà un dossier étudiant, ou aucun compte élève n'a été créé dans votre périmètre.
                            </p>
                            @can('create', \App\Models\User::class)
                                <a href="{{ route('users.create') }}" class="btn btn-warning btn-sm mt-2 fw-semibold">
                                    <i class="bi bi-person-plus-fill me-1"></i>Créer un compte utilisateur d'abord
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('students.store') }}" id="form-create-student">
                @csrf

                {{-- Bloc 1 : Rattachement Compte Utilisateur & Centre --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-person-badge me-2"></i>1. Rattachement du compte
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            {{-- Sélection du compte étudiant --}}
                            <div class="col-md-{{ auth()->user()->isSuperAdmin() ? '6' : '12' }}">
                                <label for="user_id" class="form-label fw-semibold">
                                    Compte utilisateur élève <span class="text-danger">*</span>
                                </label>
                                <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                                    <option value="">-- Sélectionner un compte élève --</option>
                                    @foreach($availableUsers as $user)
                                        <option value="{{ $user->id }}"
                                                data-center-id="{{ $user->center_id }}"
                                                data-name="{{ $user->name }}"
                                                data-phone="{{ $user->phone }}"
                                                {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->email }})
                                            @if(auth()->user()->isSuperAdmin() && $user->center)
                                                — [{{ $user->center->name }}]
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Seuls les utilisateurs avec le rôle <em>Élève</em> sans dossier existant sont listés.
                                </div>
                            </div>

                            {{-- Super Admin : filtre de centre d'affectation si souhaité --}}
                            @if(auth()->user()->isSuperAdmin())
                                <div class="col-md-6">
                                    <label for="center_id" class="form-label fw-semibold">
                                        Centre éducatif <span class="text-danger">*</span>
                                    </label>
                                    <select id="center_id" name="center_id" class="form-select @error('center_id') is-invalid @enderror" required>
                                        <option value="">-- Sélectionner le centre --</option>
                                        @foreach($centers as $center)
                                            <option value="{{ $center->id }}" {{ old('center_id') == $center->id ? 'selected' : '' }}>
                                                {{ $center->name }} ({{ $center->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('center_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        Le numéro étudiant sera automatiquement préfixé par le code du centre (ex: BER-000001).
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Bloc 2 : Informations personnelles --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-person-lines-fill me-2"></i>2. Informations personnelles
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
                                       value="{{ old('first_name') }}" required>
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
                                       value="{{ old('last_name') }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="date_of_birth" class="form-label fw-semibold">Date de naissance</label>
                                <input type="date" id="date_of_birth" name="date_of_birth"
                                       class="form-control @error('date_of_birth') is-invalid @enderror"
                                       value="{{ old('date_of_birth') }}">
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="place_of_birth" class="form-label fw-semibold">Lieu de naissance</label>
                                <input type="text" id="place_of_birth" name="place_of_birth"
                                       class="form-control @error('place_of_birth') is-invalid @enderror"
                                       value="{{ old('place_of_birth') }}" placeholder="Ex: Berlin">
                                @error('place_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="gender" class="form-label fw-semibold">Genre</label>
                                <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror">
                                    <option value="">Non précisé</option>
                                    <option value="male"   {{ old('gender') === 'male'   ? 'selected' : '' }}>Masculin</option>
                                    <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Féminin</option>
                                    <option value="other"  {{ old('gender') === 'other'  ? 'selected' : '' }}>Autre</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="nationality" class="form-label fw-semibold">Nationalité</label>
                                <input type="text" id="nationality" name="nationality"
                                       class="form-control @error('nationality') is-invalid @enderror"
                                       value="{{ old('nationality', 'Allemande') }}">
                                @error('nationality')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold">Numéro de téléphone</label>
                                <input type="text" id="phone" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone') }}" placeholder="+49 30 1234567">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bloc 3 : Adresse & Coordonnées --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-geo-alt me-2"></i>3. Coordonnées & Adresse
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="address" class="form-label fw-semibold">Adresse postale</label>
                                <input type="text" id="address" name="address"
                                       class="form-control @error('address') is-invalid @enderror"
                                       value="{{ old('address') }}" placeholder="Ex: Friedrichstraße 42">
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="postal_code" class="form-label fw-semibold">Code postal</label>
                                <input type="text" id="postal_code" name="postal_code"
                                       class="form-control @error('postal_code') is-invalid @enderror"
                                       value="{{ old('postal_code') }}" placeholder="Ex: 10117">
                                @error('postal_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="city" class="form-label fw-semibold">Ville</label>
                                <input type="text" id="city" name="city"
                                       class="form-control @error('city') is-invalid @enderror"
                                       value="{{ old('city') }}" placeholder="Ex: Berlin">
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="country" class="form-label fw-semibold">Pays</label>
                                <input type="text" id="country" name="country"
                                       class="form-control @error('country') is-invalid @enderror"
                                       value="{{ old('country', 'Germany') }}">
                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bloc 4 : Contact d'urgence & Notes --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-telephone-plus me-2"></i>4. Contact d'urgence & Statut
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="emergency_contact_name" class="form-label fw-semibold">Nom du contact</label>
                                <input type="text" id="emergency_contact_name" name="emergency_contact_name"
                                       class="form-control @error('emergency_contact_name') is-invalid @enderror"
                                       value="{{ old('emergency_contact_name') }}" placeholder="Ex: Klaus Müller">
                                @error('emergency_contact_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="emergency_contact_phone" class="form-label fw-semibold">Téléphone d'urgence</label>
                                <input type="text" id="emergency_contact_phone" name="emergency_contact_phone"
                                       class="form-control @error('emergency_contact_phone') is-invalid @enderror"
                                       value="{{ old('emergency_contact_phone') }}" placeholder="+49 170 9876543">
                                @error('emergency_contact_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="emergency_contact_relation" class="form-label fw-semibold">Lien de parenté / relation</label>
                                <input type="text" id="emergency_contact_relation" name="emergency_contact_relation"
                                       class="form-control @error('emergency_contact_relation') is-invalid @enderror"
                                       value="{{ old('emergency_contact_relation') }}" placeholder="Ex: Père, Mère, Conjoint">
                                @error('emergency_contact_relation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label for="notes" class="form-label fw-semibold">Notes & Observations administratives</label>
                                <textarea id="notes" name="notes" rows="3"
                                          class="form-control @error('notes') is-invalid @enderror"
                                          placeholder="Notes internes éventuelles...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="status" class="form-label fw-semibold">
                                    Statut initial du dossier <span class="text-danger">*</span>
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
                    <a href="{{ route('students.index') }}" class="btn btn-outline-secondary px-4">Annuler</a>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm" id="btn-submit-student">
                        <i class="bi bi-check-circle-fill me-1"></i>Enregistrer le dossier élève
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const userSelect = document.getElementById('user_id');
        const centerSelect = document.getElementById('center_id');
        const firstNameInput = document.getElementById('first_name');
        const lastNameInput = document.getElementById('last_name');
        const phoneInput = document.getElementById('phone');

        // Pré-remplir automatiquement prénom / nom / centre si sélection d'un compte
        userSelect.addEventListener('change', function () {
            const selected = userSelect.options[userSelect.selectedIndex];
            if (!selected || !selected.value) return;

            const fullName = selected.getAttribute('data-name') || '';
            const userPhone = selected.getAttribute('data-phone') || '';
            const centerId = selected.getAttribute('data-center-id');

            if (fullName && (!firstNameInput.value && !lastNameInput.value)) {
                const parts = fullName.trim().split(' ');
                if (parts.length > 1) {
                    firstNameInput.value = parts[0];
                    lastNameInput.value = parts.slice(1).join(' ');
                } else {
                    firstNameInput.value = parts[0];
                }
            }

            if (userPhone && !phoneInput.value) {
                phoneInput.value = userPhone;
            }

            if (centerSelect && centerId) {
                centerSelect.value = centerId;
            }
        });
    });
</script>
@endpush
@endsection
