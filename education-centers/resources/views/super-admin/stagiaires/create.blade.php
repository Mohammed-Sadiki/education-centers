@extends('layouts.app')

@php
    // Validation stricte : toutes les sections sont obligatoires sauf « Diplômes
    // obtenus » et « Autres documents ». Après un refus du serveur, les lignes
    // dynamiques commencées sont recréées depuis old() (les fichiers, eux, ne
    // peuvent pas être restaurés par le navigateur).
    $diplomaRows = collect(old('diplomas', []))
        ->map(fn ($row) => [
            'title' => trim((string) ($row['title'] ?? '')),
            'institution' => trim((string) ($row['institution'] ?? '')),
            'year' => trim((string) ($row['year'] ?? '')),
        ])
        ->filter(fn ($row) => $row['title'] !== '' || $row['institution'] !== '' || $row['year'] !== '')
        ->values()
        ->all() ?: [['title' => '', 'institution' => '', 'year' => '']];

    $documentRows = collect(old('other_documents', []))
        ->map(fn ($row) => [
            'title' => trim((string) ($row['title'] ?? '')),
            'type' => ($row['type'] ?? 'attestation') ?: 'attestation',
        ])
        ->filter(fn ($row) => $row['title'] !== '')
        ->values()
        ->all() ?: [['title' => '', 'type' => 'attestation']];
@endphp

@section('title', __('Nouveau dossier Stagiaire') . ' — ' . config('app.name'))
@section('description', __('Création d’un dossier administratif de stagiaire indépendant.'))

@section('breadcrumb')
    <li class="flex items-center gap-2">
        <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
        <a href="{{ route('stagiaires.index') }}" class="hover:text-brand-700 dark:hover:text-brand-400 transition-colors">{{ __('Stagiaires') }}</a>
    </li>
    <li class="flex items-center gap-2">
        <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
        <span class="font-medium text-slate-700 dark:text-slate-200">{{ __('Nouveau dossier') }}</span>
    </li>
@endsection

@section('content')
<div class="max-w-5xl mx-auto py-6">

    {{-- En-tête --}}
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 border border-indigo-200/70 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/50 dark:border-indigo-800/70 dark:text-indigo-300 mb-2">
                {{ __('🧑‍💼 Dossier administratif de stage') }}
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ __('Ajouter un stagiaire') }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Créez un dossier complet de stage avec identité CIN, documents, diplômes et prix convenu.') }}
            </p>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400" id="legende-champs-obligatoires">
                <span class="text-rose-500 font-bold">*</span> {{ __('Les champs marqués d\'un astérisque sont obligatoires.') }}
                <span class="ms-2 inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">{{ __('Facultatif') }}</span>
                {{ __('désigne les sections qui peuvent rester entièrement vides.') }}
            </p>
        </div>
        <a href="{{ route('stagiaires.index') }}" class="ec-btn-secondary">
            {{ __('← Annuler et retour') }}
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/20">
            <p class="text-sm font-bold text-rose-800 dark:text-rose-300 mb-1">{{ __('Veuillez corriger les erreurs suivantes :') }}</p>
            <ul class="list-disc list-inside text-xs text-rose-700 dark:text-rose-400 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('stagiaires.store') }}" enctype="multipart/form-data" id="form-create-stagiaire" data-stagiaire-form novalidate>
        @csrf

        {{-- ────────────────────────────────────────────────────────────
             BLOC 1 : INFORMATIONS PERSONNELLES (obligatoires)
        ──────────────────────────────────────────────────────────── --}}
        <div class="ec-card mb-6 p-6">
            <div class="flex items-center gap-2 pb-4 mb-5 border-b border-slate-100 dark:border-slate-800">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 font-bold text-sm dark:bg-indigo-950 dark:text-indigo-300">1</span>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Informations personnelles') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('État civil et coordonnées de contact') }}</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label for="first_name" class="ec-label">{{ __('Prénom') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required data-required data-label="{{ __('Prénom') }}"
                           class="ec-input w-full @error('first_name') is-invalid @enderror" placeholder="{{ __('Ex: Karim') }}">
                    @error('first_name') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="last_name" class="ec-label">{{ __('Nom') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required data-required data-label="{{ __('Nom') }}"
                           class="ec-input w-full @error('last_name') is-invalid @enderror" placeholder="{{ __('Ex: El Amrani') }}">
                    @error('last_name') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="center_id" class="ec-label">{{ __('Centre de rattachement') }} <span class="text-rose-500">*</span></label>
                    <select id="center_id" name="center_id" required data-required data-label="{{ __('Centre de rattachement') }}"
                            class="ec-input w-full @error('center_id') is-invalid @enderror">
                        <option value="">{{ __('-- Sélectionner un centre --') }}</option>
                        @foreach($centers as $c)
                            <option value="{{ $c->id }}" {{ old('center_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->translated('name') }} ({{ $c->city }})
                            </option>
                        @endforeach
                    </select>
                    @error('center_id') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="date_of_birth" class="ec-label">{{ __('Date de naissance') }} <span class="text-rose-500">*</span></label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}" required data-required data-label="{{ __('Date de naissance') }}"
                           class="ec-input w-full @error('date_of_birth') is-invalid @enderror">
                    @error('date_of_birth') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="place_of_birth" class="ec-label">{{ __('Lieu de naissance') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="place_of_birth" name="place_of_birth" value="{{ old('place_of_birth') }}" required data-required data-label="{{ __('Lieu de naissance') }}"
                           class="ec-input w-full @error('place_of_birth') is-invalid @enderror" placeholder="{{ __('Ex: Casablanca') }}">
                    @error('place_of_birth') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="gender" class="ec-label">{{ __('Sexe') }} <span class="text-rose-500">*</span></label>
                    <select id="gender" name="gender" required data-required data-label="{{ __('Sexe') }}"
                            class="ec-input w-full @error('gender') is-invalid @enderror">
                        <option value="">{{ __('-- Sélectionner --') }}</option>
                        <option value="male"   {{ old('gender') === 'male'   ? 'selected' : '' }}>{{ __('Masculin') }}</option>
                        <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>{{ __('Féminin') }}</option>
                        <option value="other"  {{ old('gender') === 'other'  ? 'selected' : '' }}>{{ __('Autre') }}</option>
                    </select>
                    @error('gender') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="nationality" class="ec-label">{{ __('Nationalité') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="nationality" name="nationality" value="{{ old('nationality', 'Marocaine') }}" required data-required data-label="{{ __('Nationalité') }}"
                           class="ec-input w-full @error('nationality') is-invalid @enderror" placeholder="{{ __('Ex: Marocaine') }}">
                    @error('nationality') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="ec-label">{{ __('Téléphone') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" required data-required data-label="{{ __('Téléphone') }}" inputmode="tel"
                           class="ec-input w-full @error('phone') is-invalid @enderror" placeholder="{{ __('Ex: +212 600 000000') }}">
                    @error('phone') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="ec-label">{{ __('Adresse e-mail') }} <span class="text-rose-500">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required data-required data-label="{{ __('Adresse e-mail') }}"
                           class="ec-input w-full @error('email') is-invalid @enderror" placeholder="stagiaire@example.com">
                    @error('email') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="address" class="ec-label">{{ __('Adresse de résidence') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="address" name="address" value="{{ old('address') }}" required data-required data-label="{{ __('Adresse de résidence') }}"
                           class="ec-input w-full @error('address') is-invalid @enderror" placeholder="{{ __('Numéro, rue, quartier, ville...') }}">
                    @error('address') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ────────────────────────────────────────────────────────────
             BLOC 2 : IDENTITÉ (CIN) (obligatoire)
        ──────────────────────────────────────────────────────────── --}}
        <div class="ec-card mb-6 p-6">
            <div class="flex items-center gap-2 pb-4 mb-5 border-b border-slate-100 dark:border-slate-800">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 font-bold text-sm dark:bg-indigo-950 dark:text-indigo-300">2</span>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Identité (CIN / Carte Nationale)') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Numéro de pièce et justificatifs scannés') }}</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label for="cin_number" class="ec-label">{{ __('Numéro CIN') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="cin_number" name="cin_number" value="{{ old('cin_number') }}" required data-required data-label="{{ __('Numéro CIN') }}"
                           class="ec-input w-full font-mono uppercase @error('cin_number') is-invalid @enderror" placeholder="{{ __('Ex: AB123456') }}">
                    @error('cin_number') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="cin_issued_date" class="ec-label">{{ __('Date de délivrance') }} <span class="text-rose-500">*</span></label>
                    <input type="date" id="cin_issued_date" name="cin_issued_date" value="{{ old('cin_issued_date') }}" required data-required data-label="{{ __('Date de délivrance') }}"
                           class="ec-input w-full @error('cin_issued_date') is-invalid @enderror">
                    @error('cin_issued_date') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="cin_issued_place" class="ec-label">{{ __('Lieu de délivrance') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="cin_issued_place" name="cin_issued_place" value="{{ old('cin_issued_place') }}" required data-required data-label="{{ __('Lieu de délivrance') }}"
                           class="ec-input w-full @error('cin_issued_place') is-invalid @enderror" placeholder="{{ __('Ex: Rabat') }}">
                    @error('cin_issued_place') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="cin_recto" class="ec-label">{{ __('Fichier CIN Recto (PDF, JPG, PNG)') }} <span class="text-rose-500">*</span></label>
                    <input type="file" id="cin_recto" name="cin_recto" accept=".pdf,.jpg,.jpeg,.png" required data-required data-label="{{ __('Fichier CIN Recto') }}" data-max-kb="5120"
                           class="ec-input w-full file:me-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1 file:text-xs file:font-semibold @error('cin_recto') is-invalid @enderror">
                    <p class="mt-1 text-[11px] text-slate-400">{{ __('Stockage sécurisé et privé (max: 5 Mo)') }}</p>
                    @error('cin_recto') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="cin_verso" class="ec-label">{{ __('Fichier CIN Verso (PDF, JPG, PNG)') }} <span class="text-rose-500">*</span></label>
                    <input type="file" id="cin_verso" name="cin_verso" accept=".pdf,.jpg,.jpeg,.png" required data-required data-label="{{ __('Fichier CIN Verso') }}" data-max-kb="5120"
                           class="ec-input w-full file:me-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1 file:text-xs file:font-semibold @error('cin_verso') is-invalid @enderror">
                    <p class="mt-1 text-[11px] text-slate-400">{{ __('Stockage sécurisé et privé (max: 5 Mo)') }}</p>
                    @error('cin_verso') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ────────────────────────────────────────────────────────────
             BLOC 3 : INFORMATIONS DU STAGE & PRIX (obligatoires)
        ──────────────────────────────────────────────────────────── --}}
        <div class="ec-card mb-6 p-6 border-2 border-indigo-200/80 dark:border-indigo-800/60 bg-gradient-to-b from-indigo-50/20 to-transparent">
            <div class="flex items-center gap-2 pb-4 mb-5 border-b border-indigo-100 dark:border-indigo-900/60">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold text-sm">3</span>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Informations du stage') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Période, type de stage et prix convenu') }}</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label for="stage_start_date" class="ec-label">{{ __('Date de début du stage') }} <span class="text-rose-500">*</span></label>
                    <input type="date" id="stage_start_date" name="stage_start_date" value="{{ old('stage_start_date') }}" required data-required data-label="{{ __('Date de début du stage') }}"
                           class="ec-input w-full @error('stage_start_date') is-invalid @enderror">
                    @error('stage_start_date') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="stage_end_date" class="ec-label">{{ __('Date de fin prévue') }} <span class="text-rose-500">*</span></label>
                    <input type="date" id="stage_end_date" name="stage_end_date" value="{{ old('stage_end_date') }}" required data-required data-label="{{ __('Date de fin prévue') }}"
                           class="ec-input w-full @error('stage_end_date') is-invalid @enderror">
                    @error('stage_end_date') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="stage_type" class="ec-label">{{ __('Type de stage') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="stage_type" name="stage_type" value="{{ old('stage_type') }}" required data-required data-label="{{ __('Type de stage') }}"
                           class="ec-input w-full @error('stage_type') is-invalid @enderror" placeholder="{{ __('Ex: Stage pratique, Stage d\'observation, Fin d\'études...') }}">
                    @error('stage_type') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                {{-- PRIX DU STAGE & DEVISE --}}
                <div class="sm:col-span-2 lg:col-span-2 rounded-xl border border-indigo-200 bg-white p-4 dark:border-indigo-900/80 dark:bg-slate-900 shadow-2xs">
                    <label for="stage_price" class="block text-sm font-bold text-indigo-950 dark:text-indigo-200 mb-1">
                        {{ __('Prix du stage') }} <span class="text-rose-500 font-bold">*</span>
                        <span class="text-slate-400 font-normal text-xs">{{ __('(montant convenu)') }}</span>
                    </label>
                    <div class="flex items-center gap-2 max-w-sm">
                        <div class="relative flex-1">
                            <input type="number" step="0.01" min="0" max="999999.99" id="stage_price" name="stage_price"
                                   value="{{ old('stage_price', '0.00') }}" required data-required data-label="{{ __('Prix du stage') }}"
                                   class="ec-input w-full text-lg font-bold pe-12 @error('stage_price') is-invalid @enderror"
                                   placeholder="0.00">
                            <span class="absolute end-3 top-2.5 font-bold text-slate-500">DH</span>
                        </div>
                        <input type="hidden" name="currency" value="MAD">
                        <span class="rounded-lg bg-indigo-50 px-3 py-2 text-sm font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                            MAD
                        </span>
                    </div>
                    @error('stage_price') <p class="ec-error mt-1">{{ $message }}</p> @enderror
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        {{ __('Montant convenu que le stagiaire doit payer pour effectuer le stage (valeur positive ou nulle, devise par défaut MAD / DH).') }}
                    </p>
                </div>

                <div>
                    <label for="status" class="ec-label">{{ __('Statut du dossier') }} <span class="text-rose-500">*</span></label>
                    <select id="status" name="status" required data-required data-label="{{ __('Statut du dossier') }}"
                            class="ec-input w-full @error('status') is-invalid @enderror">
                        <option value="nouveau"  {{ old('status', 'nouveau') === 'nouveau'  ? 'selected' : '' }}>{{ __('Nouveau') }}</option>
                        <option value="en_cours" {{ old('status') === 'en_cours' ? 'selected' : '' }}>{{ __('Stage en cours') }}</option>
                        <option value="termine"  {{ old('status') === 'termine'  ? 'selected' : '' }}>{{ __('Terminé') }}</option>
                        <option value="suspendu" {{ old('status') === 'suspendu' ? 'selected' : '' }}>{{ __('Suspendu') }}</option>
                    </select>
                    @error('status') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="notes" class="ec-label">{{ __('Notes & observations particulières') }} <span class="text-rose-500">*</span></label>
                    <textarea id="notes" name="notes" rows="2" required data-required data-label="{{ __('Notes & observations particulières') }}"
                              class="ec-input w-full @error('notes') is-invalid @enderror" placeholder="{{ __('Commentaires internes éventuels sur la convention ou le déroulement du stage...') }}">{{ old('notes') }}</textarea>
                    @error('notes') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ────────────────────────────────────────────────────────────
             BLOC 4 : DIPLÔMES — SECTION FACULTATIVE (lignes dynamiques)
        ──────────────────────────────────────────────────────────── --}}
        <div class="ec-card mb-6 p-6" x-data="{
            diplomas: @js($diplomaRows),
            addDiploma() {
                this.diplomas.push({ title: '', institution: '', year: '' });
            },
            removeDiploma(index) {
                if (this.diplomas.length > 1) {
                    this.diplomas.splice(index, 1);
                } else {
                    this.diplomas = [{ title: '', institution: '', year: '' }];
                }
            }
        }">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-4 mb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 font-bold text-sm dark:bg-indigo-950 dark:text-indigo-300">4</span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ __('Diplômes obtenus') }}
                            <span class="ms-1 inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">{{ __('Facultatif') }}</span>
                        </h2>
                        <p class="text-xs text-slate-500">{{ __('Ajoutez un ou plusieurs diplômes avec pièces jointes') }}</p>
                    </div>
                </div>
                <button type="button" @click="addDiploma()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>{{ __('+ Ajouter un diplôme') }}</span>
                </button>
            </div>

            <p class="mb-4 text-[11px] text-slate-500 dark:text-slate-400">
                {{ __('Section facultative : une ligne laissée entièrement vide n\'est pas enregistrée. Une ligne commencée doit être complétée (nom, établissement, année et fichier scanné).') }}
            </p>

            <div class="space-y-4">
                <template x-for="(dip, index) in diplomas" :key="index">
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/40 p-4 dark:border-slate-800 dark:bg-slate-900/40 relative" data-row-group="diplomas">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300" x-text="@js(__('Diplôme')) + ' #' + (index + 1)"></span>
                            <button type="button" @click="removeDiploma(index)" class="text-xs text-rose-600 hover:underline">
                                {{ __('Supprimer cette ligne') }}
                            </button>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label class="ec-label text-xs">{{ __('Nom du diplôme') }} <span class="text-rose-500" data-row-optional-star title="{{ __('Obligatoire lorsque la ligne est utilisée') }}">*</span></label>
                                <input type="text" :name="'diplomas[' + index + '][title]'" x-model="dip.title" data-row-field="title"
                                       class="ec-input w-full text-xs" placeholder="{{ __('Ex: Licence en Biologie') }}">
                            </div>
                            <div>
                                <label class="ec-label text-xs">{{ __('Établissement') }} <span class="text-rose-500" data-row-optional-star title="{{ __('Obligatoire lorsque la ligne est utilisée') }}">*</span></label>
                                <input type="text" :name="'diplomas[' + index + '][institution]'" x-model="dip.institution" data-row-field="institution"
                                       class="ec-input w-full text-xs" placeholder="{{ __('Ex: Université Hassan II') }}">
                            </div>
                            <div>
                                <label class="ec-label text-xs">{{ __('Année d\'obtention') }} <span class="text-rose-500" data-row-optional-star title="{{ __('Obligatoire lorsque la ligne est utilisée') }}">*</span></label>
                                <input type="text" :name="'diplomas[' + index + '][year]'" x-model="dip.year" data-row-field="year" inputmode="numeric" data-numeric
                                       class="ec-input w-full text-xs" placeholder="{{ __('Ex: 2023') }}">
                            </div>
                            <div>
                                <label class="ec-label text-xs">{{ __('Fichier scanné (PDF, image)') }} <span class="text-rose-500" data-row-optional-star title="{{ __('Obligatoire lorsque la ligne est utilisée') }}">*</span></label>
                                <input type="file" :name="'diplomas[' + index + '][file]'" accept=".pdf,.jpg,.jpeg,.png" data-max-kb="10240" data-row-field="file"
                                       class="ec-input w-full text-xs file:me-2 file:rounded file:border-0 file:bg-slate-200 file:px-2 file:py-0.5 file:text-[11px]">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- ────────────────────────────────────────────────────────────
             BLOC 5 : AUTRES DOCUMENTS — SECTION FACULTATIVE (lignes dynamiques)
        ──────────────────────────────────────────────────────────── --}}
        <div class="ec-card mb-8 p-6" x-data="{
            docs: @js($documentRows),
            addDoc() {
                this.docs.push({ title: '', type: 'autre' });
            },
            removeDoc(index) {
                if (this.docs.length > 1) {
                    this.docs.splice(index, 1);
                } else {
                    this.docs = [{ title: '', type: 'attestation' }];
                }
            }
        }">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-4 mb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 font-bold text-sm dark:bg-indigo-950 dark:text-indigo-300">5</span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ __('Autres documents') }}
                            <span class="ms-1 inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">{{ __('Facultatif') }}</span>
                        </h2>
                        <p class="text-xs text-slate-500">{{ __('Attestations, certificats, conventions ou pièces complémentaires') }}</p>
                    </div>
                </div>
                <button type="button" @click="addDoc()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>{{ __('+ Ajouter un document') }}</span>
                </button>
            </div>

            <p class="mb-4 text-[11px] text-slate-500 dark:text-slate-400">
                {{ __('Section facultative : une ligne laissée sans titre ni fichier n\'est pas enregistrée. Une ligne commencée doit être complétée (titre, type et fichier scanné).') }}
            </p>

            <div class="space-y-4">
                <template x-for="(doc, index) in docs" :key="index">
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/40 p-4 dark:border-slate-800 dark:bg-slate-900/40 relative" data-row-group="other_documents">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300" x-text="@js(__('Document')) + ' #' + (index + 1)"></span>
                            <button type="button" @click="removeDoc(index)" class="text-xs text-rose-600 hover:underline">
                                {{ __('Supprimer cette ligne') }}
                            </button>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <label class="ec-label text-xs">{{ __('Titre / Description') }} <span class="text-rose-500" data-row-optional-star title="{{ __('Obligatoire lorsque la ligne est utilisée') }}">*</span></label>
                                <input type="text" :name="'other_documents[' + index + '][title]'" x-model="doc.title" data-row-field="title"
                                       class="ec-input w-full text-xs" placeholder="{{ __('Ex: Convention tripartite signée') }}">
                            </div>
                            <div>
                                <label class="ec-label text-xs">{{ __('Type de pièce') }} <span class="text-rose-500" data-row-optional-star title="{{ __('Obligatoire lorsque la ligne est utilisée') }}">*</span></label>
                                <select :name="'other_documents[' + index + '][type]'" x-model="doc.type" data-row-field="type" data-row-preset
                                        class="ec-input w-full text-xs">
                                    <option value="convention">{{ __('Convention de stage') }}</option>
                                    <option value="attestation">{{ __('Attestation') }}</option>
                                    <option value="certificat">{{ __('Certificat') }}</option>
                                    <option value="cv">CV</option>
                                    <option value="autre">{{ __('Autre document') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="ec-label text-xs">{{ __('Fichier scanné (PDF, image)') }} <span class="text-rose-500" data-row-optional-star title="{{ __('Obligatoire lorsque la ligne est utilisée') }}">*</span></label>
                                <input type="file" :name="'other_documents[' + index + '][file]'" accept=".pdf,.jpg,.jpeg,.png" data-max-kb="10240" data-row-field="file"
                                       class="ec-input w-full text-xs file:me-2 file:rounded file:border-0 file:bg-slate-200 file:px-2 file:py-0.5 file:text-[11px]">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Boutons d'action --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
            <a href="{{ route('stagiaires.index') }}" class="ec-btn-secondary px-6">
                {{ __('Annuler') }}
            </a>
            <button type="submit" id="btn-submit-stagiaire" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-all">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span>{{ __('Enregistrer le stagiaire') }}</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    {{-- Validation stricte partagée avec le formulaire de modification. --}}
    @include('super-admin.stagiaires.partials.form-validation')
@endpush
