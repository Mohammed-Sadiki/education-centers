@extends('layouts.app')

@section('title', __('Nouveau dossier Stagiaire') . ' — ' . config('app.name'))
@section('description', __('Création d’un dossier administratif de stagiaire indépendant.'))

@section('breadcrumb')
    <li class="flex items-center gap-2">
        <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
        <a href="{{ route('stagiaires.dashboard') }}" class="hover:text-brand-700 dark:hover:text-brand-400 transition-colors">{{ __('Stagiaires') }}</a>
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

    <form method="POST" action="{{ route('stagiaires.store') }}" enctype="multipart/form-data" id="form-create-stagiaire">
        @csrf

        {{-- ────────────────────────────────────────────────────────────
             BLOC 1 : INFORMATIONS PERSONNELLES
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
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required
                           class="ec-input w-full @error('first_name') ec-input-error @enderror" placeholder="{{ __('Ex: Karim') }}">
                    @error('first_name') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="last_name" class="ec-label">{{ __('Nom') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required
                           class="ec-input w-full @error('last_name') ec-input-error @enderror" placeholder="{{ __('Ex: El Amrani') }}">
                    @error('last_name') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="center_id" class="ec-label">{{ __('Centre de rattachement') }}</label>
                    <select id="center_id" name="center_id" class="ec-input w-full @error('center_id') ec-input-error @enderror">
                        <option value="">{{ __('-- Aucun / Tous les centres --') }}</option>
                        @foreach($centers as $c)
                            <option value="{{ $c->id }}" {{ old('center_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->translated('name') }} ({{ $c->city }})
                            </option>
                        @endforeach
                    </select>
                    @error('center_id') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="date_of_birth" class="ec-label">{{ __('Date de naissance') }}</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}"
                           class="ec-input w-full @error('date_of_birth') ec-input-error @enderror">
                    @error('date_of_birth') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="place_of_birth" class="ec-label">{{ __('Lieu de naissance') }}</label>
                    <input type="text" id="place_of_birth" name="place_of_birth" value="{{ old('place_of_birth') }}"
                           class="ec-input w-full @error('place_of_birth') ec-input-error @enderror" placeholder="{{ __('Ex: Casablanca') }}">
                    @error('place_of_birth') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="gender" class="ec-label">{{ __('Sexe') }}</label>
                    <select id="gender" name="gender" class="ec-input w-full">
                        <option value="">{{ __('Non précisé') }}</option>
                        <option value="male"   {{ old('gender') === 'male'   ? 'selected' : '' }}>{{ __('Masculin') }}</option>
                        <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>{{ __('Féminin') }}</option>
                        <option value="other"  {{ old('gender') === 'other'  ? 'selected' : '' }}>{{ __('Autre') }}</option>
                    </select>
                </div>

                <div>
                    <label for="nationality" class="ec-label">{{ __('Nationalité') }}</label>
                    <input type="text" id="nationality" name="nationality" value="{{ old('nationality', 'Marocaine') }}"
                           class="ec-input w-full" placeholder="{{ __('Ex: Marocaine') }}">
                </div>

                <div>
                    <label for="phone" class="ec-label">{{ __('Téléphone') }}</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                           class="ec-input w-full" placeholder="{{ __('Ex: +212 600 000000') }}">
                </div>

                <div>
                    <label for="email" class="ec-label">{{ __('Adresse e-mail') }}</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="ec-input w-full @error('email') ec-input-error @enderror" placeholder="stagiaire@example.com">
                    @error('email') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="address" class="ec-label">{{ __('Adresse de résidence') }}</label>
                    <input type="text" id="address" name="address" value="{{ old('address') }}"
                           class="ec-input w-full" placeholder="{{ __('Numéro, rue, quartier, ville...') }}">
                </div>
            </div>
        </div>

        {{-- ────────────────────────────────────────────────────────────
             BLOC 2 : IDENTITÉ (CIN)
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
                    <label for="cin_number" class="ec-label">{{ __('Numéro CIN') }}</label>
                    <input type="text" id="cin_number" name="cin_number" value="{{ old('cin_number') }}"
                           class="ec-input w-full font-mono uppercase" placeholder="{{ __('Ex: AB123456') }}">
                </div>

                <div>
                    <label for="cin_issued_date" class="ec-label">{{ __('Date de délivrance') }}</label>
                    <input type="date" id="cin_issued_date" name="cin_issued_date" value="{{ old('cin_issued_date') }}"
                           class="ec-input w-full">
                </div>

                <div>
                    <label for="cin_issued_place" class="ec-label">{{ __('Lieu de délivrance') }}</label>
                    <input type="text" id="cin_issued_place" name="cin_issued_place" value="{{ old('cin_issued_place') }}"
                           class="ec-input w-full" placeholder="{{ __('Ex: Rabat') }}">
                </div>

                <div>
                    <label for="cin_recto" class="ec-label">{{ __('Fichier CIN Recto (PDF, JPG, PNG)') }}</label>
                    <input type="file" id="cin_recto" name="cin_recto" accept=".pdf,.jpg,.jpeg,.png"
                           class="ec-input w-full file:me-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1 file:text-xs file:font-semibold">
                    <p class="mt-1 text-[11px] text-slate-400">{{ __('Stockage sécurisé et privé (max: 5 Mo)') }}</p>
                </div>

                <div>
                    <label for="cin_verso" class="ec-label">{{ __('Fichier CIN Verso (PDF, JPG, PNG)') }}</label>
                    <input type="file" id="cin_verso" name="cin_verso" accept=".pdf,.jpg,.jpeg,.png"
                           class="ec-input w-full file:me-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1 file:text-xs file:font-semibold">
                    <p class="mt-1 text-[11px] text-slate-400">{{ __('Stockage sécurisé et privé (max: 5 Mo)') }}</p>
                </div>
            </div>
        </div>

        {{-- ────────────────────────────────────────────────────────────
             BLOC 3 : INFORMATIONS DU STAGE & PRIX
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
                    <label for="stage_start_date" class="ec-label">{{ __('Date de début du stage') }}</label>
                    <input type="date" id="stage_start_date" name="stage_start_date" value="{{ old('stage_start_date') }}"
                           class="ec-input w-full">
                </div>

                <div>
                    <label for="stage_end_date" class="ec-label">{{ __('Date de fin prévue') }}</label>
                    <input type="date" id="stage_end_date" name="stage_end_date" value="{{ old('stage_end_date') }}"
                           class="ec-input w-full @error('stage_end_date') ec-input-error @enderror">
                    @error('stage_end_date') <p class="ec-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="stage_type" class="ec-label">{{ __('Type de stage') }}</label>
                    <input type="text" id="stage_type" name="stage_type" value="{{ old('stage_type') }}"
                           class="ec-input w-full" placeholder="{{ __('Ex: Stage pratique, Stage d\'observation, Fin d\'études...') }}">
                </div>

                {{-- PRIX DU STAGE & DEVISE --}}
                <div class="sm:col-span-2 lg:col-span-2 rounded-xl border border-indigo-200 bg-white p-4 dark:border-indigo-900/80 dark:bg-slate-900 shadow-2xs">
                    <label for="stage_price" class="block text-sm font-bold text-indigo-950 dark:text-indigo-200 mb-1">
                        {{ __('Prix du stage') }} <span class="text-slate-400 font-normal text-xs">{{ __('(montant convenu)') }}</span>
                    </label>
                    <div class="flex items-center gap-2 max-w-sm">
                        <div class="relative flex-1">
                            <input type="number" step="0.01" min="0" max="999999.99" id="stage_price" name="stage_price"
                                   value="{{ old('stage_price', '0.00') }}"
                                   class="ec-input w-full text-lg font-bold pe-12 @error('stage_price') ec-input-error @enderror"
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
                    <label for="status" class="ec-label">{{ __('Statut du dossier') }}</label>
                    <select id="status" name="status" class="ec-input w-full">
                        <option value="nouveau"  {{ old('status', 'nouveau') === 'nouveau'  ? 'selected' : '' }}>{{ __('Nouveau') }}</option>
                        <option value="en_cours" {{ old('status') === 'en_cours' ? 'selected' : '' }}>{{ __('Stage en cours') }}</option>
                        <option value="termine"  {{ old('status') === 'termine'  ? 'selected' : '' }}>{{ __('Terminé') }}</option>
                        <option value="suspendu" {{ old('status') === 'suspendu' ? 'selected' : '' }}>{{ __('Suspendu') }}</option>
                    </select>
                </div>

                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="notes" class="ec-label">{{ __('Notes & observations particulières') }}</label>
                    <textarea id="notes" name="notes" rows="2" class="ec-input w-full" placeholder="{{ __('Commentaires internes éventuels sur la convention ou le déroulement du stage...') }}">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ────────────────────────────────────────────────────────────
             BLOC 4 : DIPLÔMES (DYNAMIQUES AVEC ALPINE.JS)
        ──────────────────────────────────────────────────────────── --}}
        <div class="ec-card mb-6 p-6" x-data="{
            diplomas: [
                { title: '', institution: '', year: '' }
            ],
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
            <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 font-bold text-sm dark:bg-indigo-950 dark:text-indigo-300">4</span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Diplômes obtenus') }}</h2>
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

            <div class="space-y-4">
                <template x-for="(dip, index) in diplomas" :key="index">
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/40 p-4 dark:border-slate-800 dark:bg-slate-900/40 relative">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300" x-text="@js(__('Diplôme')) + ' #' + (index + 1)"></span>
                            <button type="button" @click="removeDiploma(index)" class="text-xs text-rose-600 hover:underline">
                                {{ __('Supprimer cette ligne') }}
                            </button>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label class="ec-label text-xs">{{ __('Nom du diplôme') }}</label>
                                <input type="text" :name="'diplomas[' + index + '][title]'" x-model="dip.title"
                                       class="ec-input w-full text-xs" placeholder="{{ __('Ex: Licence en Biologie') }}">
                            </div>
                            <div>
                                <label class="ec-label text-xs">{{ __('Établissement') }}</label>
                                <input type="text" :name="'diplomas[' + index + '][institution]'" x-model="dip.institution"
                                       class="ec-input w-full text-xs" placeholder="{{ __('Ex: Université Hassan II') }}">
                            </div>
                            <div>
                                <label class="ec-label text-xs">{{ __('Année d\'obtention') }}</label>
                                <input type="text" :name="'diplomas[' + index + '][year]'" x-model="dip.year"
                                       class="ec-input w-full text-xs" placeholder="{{ __('Ex: 2023') }}">
                            </div>
                            <div>
                                <label class="ec-label text-xs">{{ __('Fichier scanné (PDF, image)') }}</label>
                                <input type="file" :name="'diplomas[' + index + '][file]'" accept=".pdf,.jpg,.jpeg,.png"
                                       class="ec-input w-full text-xs file:me-2 file:rounded file:border-0 file:bg-slate-200 file:px-2 file:py-0.5 file:text-[11px]">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- ────────────────────────────────────────────────────────────
             BLOC 5 : AUTRES DOCUMENTS (DYNAMIQUES AVEC ALPINE.JS)
        ──────────────────────────────────────────────────────────── --}}
        <div class="ec-card mb-8 p-6" x-data="{
            docs: [
                { title: '', type: 'attestation' }
            ],
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
            <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 font-bold text-sm dark:bg-indigo-950 dark:text-indigo-300">5</span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Autres documents') }}</h2>
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

            <div class="space-y-4">
                <template x-for="(doc, index) in docs" :key="index">
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/40 p-4 dark:border-slate-800 dark:bg-slate-900/40 relative">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300" x-text="'Document #' + (index + 1)"></span>
                            <button type="button" @click="removeDoc(index)" class="text-xs text-rose-600 hover:underline">
                                {{ __('Supprimer cette ligne') }}
                            </button>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <label class="ec-label text-xs">{{ __('Titre / Description') }}</label>
                                <input type="text" :name="'other_documents[' + index + '][title]'" x-model="doc.title"
                                       class="ec-input w-full text-xs" placeholder="{{ __('Ex: Convention tripartite signée') }}">
                            </div>
                            <div>
                                <label class="ec-label text-xs">{{ __('Type de pièce') }}</label>
                                <select :name="'other_documents[' + index + '][type]'" x-model="doc.type" class="ec-input w-full text-xs">
                                    <option value="convention">{{ __('Convention de stage') }}</option>
                                    <option value="attestation">{{ __('Attestation') }}</option>
                                    <option value="certificat">{{ __('Certificat') }}</option>
                                    <option value="cv">CV</option>
                                    <option value="autre">{{ __('Autre document') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="ec-label text-xs">{{ __('Fichier scanné (PDF, image)') }}</label>
                                <input type="file" :name="'other_documents[' + index + '][file]'" accept=".pdf,.jpg,.jpeg,.png"
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
