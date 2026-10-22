@extends('layouts.app')

@section('title', __('Modifier le stagiaire') . ' — ' . $stagiaire->first_name . ' ' . $stagiaire->last_name)

@section('content')
<div class="space-y-6">
    {{-- Fil d'Ariane & En-tête --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('super-admin.domaines') }}" class="hover:text-slate-800 dark:hover:text-slate-200">{{ __('Portail') }}</a>
                <span>/</span>
                <a href="{{ route('stagiaires.dashboard') }}" class="hover:text-slate-800 dark:hover:text-slate-200">{{ __('Stagiaires') }}</a>
                <span>/</span>
                <a href="{{ route('stagiaires.show', $stagiaire) }}" class="hover:text-slate-800 dark:hover:text-slate-200">{{ $stagiaire->first_name }} {{ $stagiaire->last_name }}</a>
                <span>/</span>
                <span class="text-slate-700 font-semibold dark:text-slate-300">{{ __('Modifier') }}</span>
            </nav>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                {{ __('Modifier le dossier :') }} {{ $stagiaire->first_name }} {{ $stagiaire->last_name }}
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ __('Mise à jour des informations personnelles, pièce d\'identité, documents et conditions du stage.') }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('stagiaires.show', $stagiaire) }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 transition">
                <x-icon name="chevron-left" class="h-4 w-4" />
                <span>{{ __('Voir le dossier') }}</span>
            </a>
        </div>
    </div>

    {{-- Erreurs globales --}}
    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
            <div class="font-bold mb-1 flex items-center gap-1.5">
                <x-icon name="warning" class="h-4 w-4" />
                <span>{{ __('Veuillez corriger les erreurs ci-dessous :') }}</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('stagiaires.update', $stagiaire) }}"
          method="POST"
          enctype="multipart/form-data"
          x-data="{
              newDiplomas: [],
              newDocuments: [],
              addDiploma() {
                  this.newDiplomas.push({ id: Date.now(), title: '', institution: '', year: '' });
              },
              removeDiploma(index) {
                  this.newDiplomas.splice(index, 1);
              },
              addDocument() {
                  this.newDocuments.push({ id: Date.now(), title: '', type: 'autre' });
              },
              removeDocument(index) {
                  this.newDocuments.splice(index, 1);
              }
          }"
          class="space-y-6">
        @csrf
        @method('PUT')

        {{-- SECTION 1 : Informations Personnelles --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-slate-800">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300">
                    <x-icon name="user" class="h-5 w-5" />
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('1. Informations personnelles') }}</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Coordonnées et état civil du stagiaire.') }}</p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Nom --}}
                <div>
                    <label for="last_name" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                        {{ __('Nom de famille') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="last_name" id="last_name" required
                           value="{{ old('last_name', $stagiaire->last_name) }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('last_name') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
                </div>

                {{-- Prénom --}}
                <div>
                    <label for="first_name" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                        {{ __('Prénom') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="first_name" id="first_name" required
                           value="{{ old('first_name', $stagiaire->first_name) }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('first_name') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
                </div>

                {{-- Genre --}}
                <div>
                    <label for="gender" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('Sexe / Genre') }}</label>
                    <select name="gender" id="gender"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="">{{ __('Sélectionner') }}</option>
                        <option value="homme" {{ old('gender', $stagiaire->gender) === 'homme' ? 'selected' : '' }}>Homme</option>
                        <option value="femme" {{ old('gender', $stagiaire->gender) === 'femme' ? 'selected' : '' }}>Femme</option>
                        <option value="autre" {{ old('gender', $stagiaire->gender) === 'autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>

                {{-- Date de naissance --}}
                <div>
                    <label for="birth_date" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('Date de naissance') }}</label>
                    <input type="date" name="birth_date" id="birth_date"
                           value="{{ old('birth_date', $stagiaire->birth_date?->format('Y-m-d')) }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                {{-- Lieu de naissance --}}
                <div>
                    <label for="birth_place" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('Lieu de naissance') }}</label>
                    <input type="text" name="birth_place" id="birth_place"
                           value="{{ old('birth_place', $stagiaire->birth_place) }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                {{-- Nationalité --}}
                <div>
                    <label for="nationality" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('Nationalité') }}</label>
                    <input type="text" name="nationality" id="nationality"
                           value="{{ old('nationality', $stagiaire->nationality) }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                {{-- Téléphone --}}
                <div>
                    <label for="phone" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('Téléphone') }}</label>
                    <input type="tel" name="phone" id="phone"
                           value="{{ old('phone', $stagiaire->phone) }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('Adresse email') }}</label>
                    <input type="email" name="email" id="email"
                           value="{{ old('email', $stagiaire->email) }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('email') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
                </div>

                {{-- Centre de rattachement --}}
                <div>
                    <label for="center_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('Centre d\'accueil') }}</label>
                    <select name="center_id" id="center_id"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="">{{ __('Sélectionner un centre') }}</option>
                        @foreach($centers as $c)
                            <option value="{{ $c->id }}" {{ old('center_id', $stagiaire->center_id) == $c->id ? 'selected' : '' }}>
                                {{ $c->translated('name') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Adresse --}}
                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="address" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('Adresse résidentielle') }}</label>
                    <textarea name="address" id="address" rows="2"
                              class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">{{ old('address', $stagiaire->address) }}</textarea>
                </div>
            </div>
        </div>

        {{-- SECTION 2 : Identité & CIN --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-slate-800">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                    <x-icon name="shield" class="h-5 w-5" />
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('2. Carte Nationale d\'Identité (CIN)') }}</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Numéro et justificatifs scannés.') }}</p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                {{-- Numéro CIN --}}
                <div>
                    <label for="cin_number" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('Numéro CIN') }}</label>
                    <input type="text" name="cin_number" id="cin_number"
                           value="{{ old('cin_number', $stagiaire->cin_number) }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-mono text-slate-900 uppercase focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                {{-- Date de délivrance --}}
                <div>
                    <label for="cin_delivered_at" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('Date de délivrance') }}</label>
                    <input type="date" name="cin_delivered_at" id="cin_delivered_at"
                           value="{{ old('cin_delivered_at', $stagiaire->cin_delivered_at?->format('Y-m-d')) }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                {{-- Lieu de délivrance --}}
                <div>
                    <label for="cin_delivered_place" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('Lieu de délivrance') }}</label>
                    <input type="text" name="cin_delivered_place" id="cin_delivered_place"
                           value="{{ old('cin_delivered_place', $stagiaire->cin_delivered_place) }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 pt-6 border-t border-slate-100 dark:border-slate-800">
                {{-- CIN Recto --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 dark:border-slate-700/60 dark:bg-slate-800/40">
                    <label for="cin_recto" class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                        {{ __('Remplacer CIN — Recto') }}
                    </label>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-2">{{ __('Formats acceptés : PDF, PNG, JPG (max 5 Mo)') }}</p>

                    @if($stagiaire->cin_recto_path)
                        <div class="mb-3 flex items-center justify-between rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                            <span>{{ __('Fichier actuel sauvegardé') }}</span>
                            <a href="{{ route('stagiaires.cin-download', [$stagiaire, 'recto']) }}" class="font-bold underline">{{ __('Télécharger') }}</a>
                        </div>
                    @endif

                    <input type="file" name="cin_recto" id="cin_recto" accept=".pdf,.png,.jpg,.jpeg"
                           class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-indigo-700 dark:text-slate-300">
                </div>

                {{-- CIN Verso --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 dark:border-slate-700/60 dark:bg-slate-800/40">
                    <label for="cin_verso" class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                        {{ __('Remplacer CIN — Verso') }}
                    </label>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-2">{{ __('Formats acceptés : PDF, PNG, JPG (max 5 Mo)') }}</p>

                    @if($stagiaire->cin_verso_path)
                        <div class="mb-3 flex items-center justify-between rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                            <span>{{ __('Fichier actuel sauvegardé') }}</span>
                            <a href="{{ route('stagiaires.cin-download', [$stagiaire, 'verso']) }}" class="font-bold underline">{{ __('Télécharger') }}</a>
                        </div>
                    @endif

                    <input type="file" name="cin_verso" id="cin_verso" accept=".pdf,.png,.jpg,.jpeg"
                           class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-indigo-700 dark:text-slate-300">
                </div>
            </div>
        </div>

        {{-- SECTION 3 : Informations du Stage & Prix du Stage --}}
        <div class="rounded-2xl border-2 border-purple-300/80 bg-purple-50/30 p-6 shadow-xs dark:border-purple-900/50 dark:bg-purple-950/10">
            <div class="flex items-center gap-3 border-b border-purple-200 pb-4 dark:border-purple-900/40">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-600 text-white shadow-2xs">
                    <x-icon name="briefcase" class="h-5 w-5" />
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('3. Informations du stage & Prix convenu') }}</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Modalités administratives et tarification du stage.') }}</p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Date début --}}
                <div>
                    <label for="stage_start_date" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                        {{ __('Date de début du stage') }}
                    </label>
                    <input type="date" name="stage_start_date" id="stage_start_date"
                           value="{{ old('stage_start_date', $stagiaire->stage_start_date?->format('Y-m-d')) }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                {{-- Date fin --}}
                <div>
                    <label for="stage_end_date" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                        {{ __('Date de fin prévue') }}
                    </label>
                    <input type="date" name="stage_end_date" id="stage_end_date"
                           value="{{ old('stage_end_date', $stagiaire->stage_end_date?->format('Y-m-d')) }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                {{-- Type de stage --}}
                <div>
                    <label for="stage_type" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                        {{ __('Type de stage') }}
                    </label>
                    <input type="text" name="stage_type" id="stage_type"
                           placeholder="{{ __('ex: Stage d\'observation, Stage pratique') }}"
                           value="{{ old('stage_type', $stagiaire->stage_type) }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                {{-- Statut --}}
                <div>
                    <label for="status" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('Statut du dossier') }}</label>
                    <select name="status" id="status"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="nouveau" {{ old('status', $stagiaire->status) === 'nouveau' ? 'selected' : '' }}>{{ __('Nouveau') }}</option>
                        <option value="en_cours" {{ old('status', $stagiaire->status) === 'en_cours' ? 'selected' : '' }}>{{ __('En cours') }}</option>
                        <option value="termine" {{ old('status', $stagiaire->status) === 'termine' ? 'selected' : '' }}>{{ __('Terminé') }}</option>
                        <option value="annule" {{ old('status', $stagiaire->status) === 'annule' ? 'selected' : '' }}>{{ __('Annulé') }}</option>
                    </select>
                </div>
            </div>

            {{-- BLOC PRIX DU STAGE & DEVISE --}}
            <div class="mt-6 rounded-xl border border-purple-200 bg-white p-5 shadow-xs dark:border-purple-900/60 dark:bg-slate-900">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label for="stage_price" class="block text-xs font-bold text-purple-900 dark:text-purple-300">
                            {{ __('Prix du stage') }} <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-1.5">
                            {{ __('Montant convenu que le stagiaire doit verser pour effectuer son stage.') }}
                        </p>
                        <div class="relative rounded-xl">
                            <input type="number" step="0.01" min="0" max="999999.99" name="stage_price" id="stage_price" required
                                   value="{{ old('stage_price', $stagiaire->stage_price) }}"
                                   class="block w-full rounded-xl border border-purple-300 bg-purple-50/40 px-3.5 py-2.5 text-base font-bold text-purple-950 focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20 dark:border-purple-800 dark:bg-slate-800 dark:text-white">
                        </div>
                        @error('stage_price') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="currency" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            {{ __('Devise') }}
                        </label>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-1.5">{{ __('Monnaie de facturation') }}</p>
                        <select name="currency" id="currency"
                                class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            <option value="MAD" {{ old('currency', $stagiaire->currency ?? 'MAD') === 'MAD' ? 'selected' : '' }}>MAD (DH)</option>
                            <option value="EUR" {{ old('currency', $stagiaire->currency) === 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                            <option value="USD" {{ old('currency', $stagiaire->currency) === 'USD' ? 'selected' : '' }}>USD ($)</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div class="mt-4">
                <label for="notes" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('Observations / Notes sur le stage') }}</label>
                <textarea name="notes" id="notes" rows="2"
                          class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">{{ old('notes', $stagiaire->notes) }}</textarea>
            </div>
        </div>

        {{-- SECTION 4 : Diplômes enregistrés & Ajout --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                        <x-icon name="certificate" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('4. Diplômes du stagiaire') }}</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Gérez les diplômes actuels ou ajoutez-en de nouveaux.') }}</p>
                    </div>
                </div>

                <button type="button" @click="addDiploma()"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 shadow-2xs hover:bg-amber-100 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300 transition">
                    <x-icon name="plus" class="h-4 w-4" />
                    <span>{{ __('+ Ajouter un diplôme') }}</span>
                </button>
            </div>

            {{-- Diplômes actuels --}}
            @if($stagiaire->diplomas->isNotEmpty())
                <div class="mt-4 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Diplômes existants :') }}</h3>
                    @foreach($stagiaire->diplomas as $diploma)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3 text-xs dark:border-slate-700/60 dark:bg-slate-800/40">
                            <div>
                                <span class="font-bold text-slate-900 dark:text-white">{{ $diploma->title }}</span>
                                <span class="text-slate-500 dark:text-slate-400">
                                    — {{ $diploma->institution ?? __('Établissement non renseigné') }} ({{ $diploma->year ?? __('Année non renseignée') }})
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($diploma->file_path)
                                    <a href="{{ route('stagiaires.diploma-download', [$stagiaire, $diploma]) }}"
                                       class="text-indigo-600 hover:underline dark:text-indigo-400 font-semibold">{{ __('Télécharger') }}</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Nouveaux Diplômes (Alpine.js) --}}
            <div class="mt-4 space-y-3" x-show="newDiplomas.length > 0">
                <h3 class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">{{ __('Nouveaux diplômes à ajouter :') }}</h3>
                <template x-for="(dip, idx) in newDiplomas" :key="dip.id">
                    <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-4 dark:border-amber-900/60 dark:bg-amber-950/20">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-amber-900 dark:text-amber-200" x-text="@js(__('Nouveau diplôme')) + ' #' + (idx + 1)"></span>
                            <button type="button" @click="removeDiploma(idx)"
                                    class="text-rose-600 hover:text-rose-800 dark:text-rose-400 text-xs font-semibold">
                                {{ __('Supprimer') }}
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">{{ __('Intitulé du diplôme *') }}</label>
                                <input type="text" :name="'diplomas[' + idx + '][title]'" required
                                       class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">{{ __('Établissement') }}</label>
                                <input type="text" :name="'diplomas[' + idx + '][institution]'"
                                       class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">{{ __('Année d\'obtention') }}</label>
                                <input type="number" min="1950" max="2100" :name="'diplomas[' + idx + '][year]'"
                                       class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">{{ __('Fichier justificatif (PDF, image)') }}</label>
                                <input type="file" :name="'diplomas[' + idx + '][file]'" accept=".pdf,.png,.jpg,.jpeg"
                                       class="mt-1 block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-amber-600 file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-white hover:file:bg-amber-700 dark:text-slate-300">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- SECTION 5 : Autres Documents & Ajout --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-100 text-teal-700 dark:bg-teal-950 dark:text-teal-300">
                        <x-icon name="document" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('5. Autres documents administratifs') }}</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Attestations, certificats ou conventions annexes.') }}</p>
                    </div>
                </div>

                <button type="button" @click="addDocument()"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-teal-300 bg-teal-50 px-3 py-1.5 text-xs font-bold text-teal-800 shadow-2xs hover:bg-teal-100 dark:border-teal-900/60 dark:bg-teal-950/40 dark:text-teal-300 transition">
                    <x-icon name="plus" class="h-4 w-4" />
                    <span>{{ __('+ Ajouter un document') }}</span>
                </button>
            </div>

            {{-- Documents actuels --}}
            @if($stagiaire->documents->isNotEmpty())
                <div class="mt-4 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Documents existants :') }}</h3>
                    @foreach($stagiaire->documents as $doc)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3 text-xs dark:border-slate-700/60 dark:bg-slate-800/40">
                            <div>
                                <span class="font-bold text-slate-900 dark:text-white">{{ $doc->title }}</span>
                                <span class="rounded bg-teal-100 px-1.5 py-0.5 text-[10px] font-semibold text-teal-800 dark:bg-teal-950 dark:text-teal-300 ml-2">
                                    {{ ucfirst($doc->type) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('stagiaires.document-download', [$stagiaire, $doc]) }}"
                                   class="text-teal-600 hover:underline dark:text-teal-400 font-semibold">{{ __('Télécharger') }}</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Nouveaux Documents (Alpine.js) --}}
            <div class="mt-4 space-y-3" x-show="newDocuments.length > 0">
                <h3 class="text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-teal-400">{{ __('Nouveaux documents à ajouter :') }}</h3>
                <template x-for="(doc, idx) in newDocuments" :key="doc.id">
                    <div class="rounded-xl border border-teal-200 bg-teal-50/40 p-4 dark:border-teal-900/60 dark:bg-teal-950/20">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-teal-900 dark:text-teal-200" x-text="'Nouveau document #' + (idx + 1)"></span>
                            <button type="button" @click="removeDocument(idx)"
                                    class="text-rose-600 hover:text-rose-800 dark:text-rose-400 text-xs font-semibold">
                                {{ __('Supprimer') }}
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">{{ __('Titre du document *') }}</label>
                                <input type="text" :name="'other_documents[' + idx + '][title]'" required
                                       class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">{{ __('Type') }}</label>
                                <select :name="'other_documents[' + idx + '][type]'"
                                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                    <option value="autre">{{ __('Autre') }}</option>
                                    <option value="attestation">{{ __('Attestation') }}</option>
                                    <option value="certificat">{{ __('Certificat') }}</option>
                                    <option value="convention">{{ __('Convention') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">{{ __('Fichier *') }}</label>
                                <input type="file" :name="'other_documents[' + idx + '][file]'" accept=".pdf,.png,.jpg,.jpeg" required
                                       class="mt-1 block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-600 file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-white hover:file:bg-teal-700 dark:text-slate-300">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Barre d'actions --}}
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('stagiaires.show', $stagiaire) }}"
               class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition">
                {{ __('Annuler') }}
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-6 py-2.5 text-xs font-bold text-white shadow-md hover:bg-purple-700 transition">
                <x-icon name="check" class="h-4 w-4" />
                <span>{{ __('Enregistrer les modifications') }}</span>
            </button>
        </div>
    </form>
</div>
@endsection
