@extends('layouts.standalone')

@section('title', __('Ajouter un élève') . ' — ' . config('app.name'))
@section('description', __('Création d\'un dossier élève avec inscription à une formation.'))
@section('standalone_title', __('Ajouter un élève'))

@php
    $authUser = auth()->user();
    $isCenterAdmin = $authUser->isCenterAdmin();
    $forcedCenter = $isCenterAdmin ? $authUser->center : null;
    $backUrl = $isCenterAdmin ? route('center-admin.domaines') : route('super-admin.domaines');
@endphp

@section('standalone_back_url', $backUrl)
@section('standalone_back_label', __('Retour aux domaines'))

@section('content')
<div class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6"
     x-data="studentFormApp({
         formations: @js($formationsData),
         isCenterAdmin: @js($isCenterAdmin),
         forcedCenterId: @js($isCenterAdmin ? (int) $authUser->center_id : null),
     })">

    {{-- Titre de la page (le retour aux domaines est dans la topbar) --}}
    <x-ui.page-header :title="__('Ajouter un élève')" :description="__('Rattachement au centre, choix du domaine et de la formation, puis informations de l\'élève.')" />

    {{-- Bandeau centre automatique (Admin Centre) : le centre n'est pas modifiable --}}
    @if($isCenterAdmin)
        <div class="mb-4 flex items-center gap-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-200">
            <x-icon name="building" class="h-5 w-5 shrink-0" />
            <div>
                {{ __('Centre') }} : <strong>{{ $forcedCenter?->translated('name') ?? $forcedCenter?->name }}</strong>
                — {{ __('Le centre est déterminé automatiquement à partir de votre compte et ne peut pas être modifié.') }}
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('students.store') }}" id="form-create-student" class="space-y-4">
        @csrf

        {{-- Bloc 1 : Centre & Domaine --}}
        <x-ui.card>
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="flex items-center gap-2 text-base font-bold text-brand-700 dark:text-brand-300">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-100 text-xs font-extrabold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">1</span>
                    <x-icon name="building" class="h-5 w-5" />
                    {{ __('Centre & domaine de formation') }}
                </h2>
            </div>
            <div class="grid gap-4 p-5 sm:grid-cols-2">
                @if($isCenterAdmin)
                    {{-- Aucun champ de sélection du centre : rattachement automatique --}}
                    <div class="sm:col-span-2">
                        <label class="ec-label">{{ __('Centre de formation') }}</label>
                        <div class="ec-input flex items-center justify-between bg-slate-50 text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                            <span>{{ $forcedCenter?->translated('name') ?? $forcedCenter?->name }}</span>
                            <span class="ec-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400">{{ __('Automatique') }}</span>
                        </div>
                        <p class="ec-help">{{ __('L\'élève sera rattaché exclusivement à ce centre.') }}</p>
                    </div>
                @else
                    <div>
                        <label for="center_id" class="ec-label">{{ __('Centre de formation') }} <span class="text-red-500">*</span></label>
                        <select id="center_id" name="center_id" class="ec-select @error('center_id') is-invalid @enderror" required>
                            <option value="">{{ __('— Sélectionner le centre —') }}</option>
                            @foreach($centers as $center)
                                <option value="{{ $center->id }}" @selected(old('center_id') == $center->id)>
                                    {{ $center->translated('name') }} ({{ $center->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('center_id') <p class="ec-error">{{ $message }}</p> @enderror
                        <p class="ec-help">{{ __('Le numéro étudiant sera automatiquement préfixé par le code du centre (ex : BER-000001).') }}</p>
                    </div>
                @endif

                <div class="sm:col-span-2">
                    <label for="domain" class="ec-label">{{ __('Domaine de formation') }} <span class="text-red-500">*</span></label>
                    <select id="domain" name="domain" class="ec-select @error('domain') is-invalid @enderror" required>
                        <option value="">{{ __('— Sélectionner le domaine —') }}</option>
                        @foreach($domains as $domain)
                            <option value="{{ $domain->slug }}" @selected(old('domain') === $domain->slug)>{{ $domain->name }}</option>
                        @endforeach
                    </select>
                    @error('domain') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-ui.card>

        {{-- Bloc 2 : Formation --}}
        <x-ui.card>
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="flex items-center gap-2 text-base font-bold text-brand-700 dark:text-brand-300">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-100 text-xs font-extrabold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">2</span>
                    <x-icon name="book" class="h-5 w-5" />
                    {{ __('Formation') }}
                </h2>
            </div>
            <div class="p-5">
                <label for="formation_id" class="ec-label">{{ __('Formation souhaitée') }}</label>
                <select id="formation_id" name="formation_id" class="ec-select @error('formation_id') is-invalid @enderror">
                    <option value="">{{ __('— Aucune inscription (créer seulement le dossier) —') }}</option>
                    @foreach($formationsData as $formation)
                        <option value="{{ $formation['id'] }}"
                                data-center-id="{{ $formation['center_id'] }}"
                                data-domain="{{ $formation['domain'] }}"
                                @selected(old('formation_id') == $formation['id'])>
                            {{ $formation['name'] }}
                        </option>
                    @endforeach
                </select>
                @error('formation_id') <p class="ec-error">{{ $message }}</p> @enderror

                {{-- États dynamiques --}}
                <p x-show="!needCenterDomain" class="ec-help">
                    {{ __('Sélectionnez d\'abord le centre et le domaine : seules les formations correspondantes seront proposées.') }}
                </p>
                <div x-show="noFormation" x-cloak class="mt-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-400">
                    {{ __('Aucune formation n\'est disponible pour ce centre et ce domaine. Le dossier élève sera créé sans inscription à une formation.') }}
                </div>
                <p x-show="formationOk" x-cloak class="ec-help">
                    <span x-text="visibleFormationCount"></span> {{ __('formation(s) disponible(s) pour ce centre et ce domaine.') }}
                </p>
            </div>
        </x-ui.card>

        {{-- Bloc 3 : Informations personnelles de l'élève --}}
        <x-ui.card>
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="flex items-center gap-2 text-base font-bold text-brand-700 dark:text-brand-300">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-100 text-xs font-extrabold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">3</span>
                    <x-icon name="user" class="h-5 w-5" />
                    {{ __('Informations de l\'élève') }}
                </h2>
            </div>
            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <p class="ec-help sm:col-span-2">
                    {{ __('Aucun compte de connexion n\'est requis : le dossier administratif est créé directement. Un compte pourra être associé ultérieurement si besoin.') }}
                </p>

                <div>
                    <label for="first_name" class="ec-label">{{ __('Prénom') }} <span class="text-red-500">*</span></label>
                    <input type="text" id="first_name" name="first_name" class="ec-input @error('first_name') is-invalid @enderror"
                           value="{{ old('first_name') }}" required>
                    @error('first_name') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="last_name" class="ec-label">{{ __('Nom') }} <span class="text-red-500">*</span></label>
                    <input type="text" id="last_name" name="last_name" class="ec-input @error('last_name') is-invalid @enderror"
                           value="{{ old('last_name') }}" required>
                    @error('last_name') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="date_of_birth" class="ec-label">{{ __('Date de naissance') }}</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="ec-input @error('date_of_birth') is-invalid @enderror"
                           value="{{ old('date_of_birth') }}">
                    @error('date_of_birth') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="place_of_birth" class="ec-label">{{ __('Lieu de naissance') }}</label>
                    <input type="text" id="place_of_birth" name="place_of_birth" class="ec-input @error('place_of_birth') is-invalid @enderror"
                           value="{{ old('place_of_birth') }}" placeholder="{{ __('Ex : Berlin') }}">
                    @error('place_of_birth') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="gender" class="ec-label">{{ __('Genre') }}</label>
                    <select id="gender" name="gender" class="ec-select @error('gender') is-invalid @enderror">
                        <option value="">{{ __('Non précisé') }}</option>
                        <option value="male" @selected(old('gender') === 'male')>{{ __('Masculin') }}</option>
                        <option value="female" @selected(old('gender') === 'female')>{{ __('Féminin') }}</option>
                        <option value="other" @selected(old('gender') === 'other')>{{ __('Autre') }}</option>
                    </select>
                    @error('gender') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="nationality" class="ec-label">{{ __('Nationalité') }}</label>
                    <input type="text" id="nationality" name="nationality" class="ec-input @error('nationality') is-invalid @enderror"
                           value="{{ old('nationality', 'Allemande') }}">
                    @error('nationality') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="phone" class="ec-label">{{ __('Numéro de téléphone') }}</label>
                    <input type="text" id="phone" name="phone" class="ec-input @error('phone') is-invalid @enderror"
                           value="{{ old('phone') }}" placeholder="+49 30 1234567">
                    @error('phone') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-ui.card>

        {{-- Bloc 4 : Adresse, contact d'urgence & statut --}}
        <x-ui.card>
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="flex items-center gap-2 text-base font-bold text-brand-700 dark:text-brand-300">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-100 text-xs font-extrabold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">4</span>
                    <x-icon name="map" class="h-5 w-5" />
                    {{ __('Adresse, contact d\'urgence & statut') }}
                </h2>
            </div>
            <div class="grid gap-4 p-5 sm:grid-cols-3">
                <div class="sm:col-span-3">
                    <label for="address" class="ec-label">{{ __('Adresse postale') }}</label>
                    <input type="text" id="address" name="address" class="ec-input @error('address') is-invalid @enderror"
                           value="{{ old('address') }}" placeholder="{{ __('Ex : Friedrichstraße 42') }}">
                    @error('address') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="postal_code" class="ec-label">{{ __('Code postal') }}</label>
                    <input type="text" id="postal_code" name="postal_code" class="ec-input @error('postal_code') is-invalid @enderror"
                           value="{{ old('postal_code') }}" placeholder="{{ __('Ex : 10117') }}">
                    @error('postal_code') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="city" class="ec-label">{{ __('Ville') }}</label>
                    <input type="text" id="city" name="city" class="ec-input @error('city') is-invalid @enderror"
                           value="{{ old('city') }}" placeholder="{{ __('Ex : Berlin') }}">
                    @error('city') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="country" class="ec-label">{{ __('Pays') }}</label>
                    <input type="text" id="country" name="country" class="ec-input @error('country') is-invalid @enderror"
                           value="{{ old('country', 'Germany') }}">
                    @error('country') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="emergency_contact_name" class="ec-label">{{ __('Nom du contact d\'urgence') }}</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="ec-input @error('emergency_contact_name') is-invalid @enderror"
                           value="{{ old('emergency_contact_name') }}" placeholder="{{ __('Ex : Klaus Müller') }}">
                    @error('emergency_contact_name') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="emergency_contact_phone" class="ec-label">{{ __('Téléphone d\'urgence') }}</label>
                    <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" class="ec-input @error('emergency_contact_phone') is-invalid @enderror"
                           value="{{ old('emergency_contact_phone') }}" placeholder="+49 170 9876543">
                    @error('emergency_contact_phone') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="emergency_contact_relation" class="ec-label">{{ __('Lien avec le contact d\'urgence') }}</label>
                    <input type="text" id="emergency_contact_relation" name="emergency_contact_relation" class="ec-input @error('emergency_contact_relation') is-invalid @enderror"
                           value="{{ old('emergency_contact_relation') }}" placeholder="{{ __('Ex : Père, Mère, Conjoint') }}">
                    @error('emergency_contact_relation') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="notes" class="ec-label">{{ __('Notes & observations administratives') }}</label>
                    <textarea id="notes" name="notes" rows="2" class="ec-textarea @error('notes') is-invalid @enderror"
                              placeholder="{{ __('Notes internes éventuelles…') }}">{{ old('notes') }}</textarea>
                    @error('notes') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="status" class="ec-label">{{ __('Statut initial du dossier') }} <span class="text-red-500">*</span></label>
                    <select id="status" name="status" class="ec-select @error('status') is-invalid @enderror" required>
                        <option value="active" @selected(old('status', 'active') === 'active')>{{ __('Actif') }}</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>{{ __('Inactif') }}</option>
                    </select>
                    @error('status') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-ui.card>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center justify-end gap-3 pb-6">
            <a href="{{ route('students.index') }}" class="ec-btn-secondary">{{ __('Annuler') }}</a>
            <button type="submit" class="ec-btn-primary px-6" id="btn-submit-student">
                <x-icon name="check" class="h-4 w-4" />
                {{ __('Enregistrer l\'élève') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function studentFormApp(config) {
        return {
            formations: config.formations,
            isCenterAdmin: config.isCenterAdmin,
            forcedCenterId: config.forcedCenterId,
            centerId: document.getElementById('center_id')?.value || (config.isCenterAdmin ? String(config.forcedCenterId) : ''),
            domain: document.getElementById('domain')?.value || '',
            visibleFormationCount: 0,

            get effectiveCenterId() {
                return this.isCenterAdmin ? String(this.forcedCenterId) : String(this.centerId || '');
            },

            get needCenterDomain() {
                return this.effectiveCenterId !== '' && this.domain !== '';
            },

            get availableFormations() {
                if (!this.needCenterDomain) return [];
                return this.formations.filter(f =>
                    String(f.center_id) === this.effectiveCenterId && f.domain === this.domain
                );
            },

            get noFormation() {
                return this.needCenterDomain && this.availableFormations.length === 0;
            },

            get formationOk() {
                return this.needCenterDomain && this.availableFormations.length > 0;
            },

            init() {
                const centerSelect = document.getElementById('center_id');
                const domainSelect = document.getElementById('domain');

                // Synchronise l'état Alpine avec les selects (natifs, compatibles sans JS).
                centerSelect?.addEventListener('change', () => { this.centerId = centerSelect.value; this.applyCascade(); });
                domainSelect?.addEventListener('change', () => { this.domain = domainSelect.value; this.applyCascade(); });

                // Centre pré-sélectionné (Admin Centre ou old() du Super Admin).
                if (this.isCenterAdmin && centerSelect) this.centerId = this.forcedCenterId;
                this.applyCascade();
            },

            applyCascade() {
                const center = this.effectiveCenterId;

                // Filtrage des options de formation (centre + domaine).
                const formationSelect = document.getElementById('formation_id');
                let visible = 0;
                formationSelect.querySelectorAll('option[data-center-id]').forEach((option) => {
                    const match = center !== '' && option.dataset.centerId === center
                        && (this.domain === '' || option.dataset.domain === this.domain);
                    option.hidden = !match;
                    option.disabled = !match;
                    if (match) visible++;
                });
                this.visibleFormationCount = visible;

                // Réinitialiser la formation si elle n'est plus valide.
                const selectedFormation = formationSelect.selectedOptions[0];
                if (selectedFormation && selectedFormation.dataset.centerId
                    && (selectedFormation.hidden || selectedFormation.disabled)) {
                    formationSelect.value = '';
                }
                // Sans domaine choisi, aucune inscription ne peut être transmise.
                if (this.domain === '' && selectedFormation?.dataset?.centerId) {
                    formationSelect.value = '';
                }
            },
        };
    }
</script>
@endpush
