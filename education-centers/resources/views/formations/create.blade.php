@extends('layouts.standalone')

@section('title', __('Ajouter une formation') . ' — ' . config('app.name'))
@section('description', __('Création d\'un programme de formation pour un centre et un domaine.'))
@section('standalone_title', __('Ajouter une formation'))

@php
    $authUser = auth()->user();
    $isCenterAdmin = $authUser->isCenterAdmin();
    $forcedCenter = $isCenterAdmin ? $authUser->center : null;
    $backUrl = $isCenterAdmin ? route('center-admin.domaines') : route('super-admin.domaines');
@endphp

@section('standalone_back_url', $backUrl)
@section('standalone_back_label', __('Retour aux domaines'))

@section('content')
<div class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6">

    {{-- Titre de la page (le retour aux domaines est dans la topbar) --}}
    <x-ui.page-header :title="__('Ajouter une formation')" :description="__('Rattachement au centre et au domaine, puis informations du programme.')" />

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

    <form method="POST" action="{{ route('formations.store') }}" id="form-create-formation" class="space-y-4">
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
                        <p class="ec-help">{{ __('La formation sera rattachée exclusivement à ce centre.') }}</p>
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
                        <p class="ec-help">{{ __('Le code formation doit être unique au sein du centre choisi.') }}</p>
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

        {{-- Bloc 2 : Informations générales --}}
        <x-ui.card>
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="flex items-center gap-2 text-base font-bold text-brand-700 dark:text-brand-300">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-100 text-xs font-extrabold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">2</span>
                    <x-icon name="book" class="h-5 w-5" />
                    {{ __('Informations générales') }}
                </h2>
            </div>
            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <div class="sm:col-span-1">
                    <label for="name" class="ec-label">{{ __('Intitulé de la formation') }} <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" class="ec-input @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" placeholder="{{ __('Ex : Deutsch A1 — Débutant') }}" required>
                    @error('name') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-1">
                    <label for="code" class="ec-label">{{ __('Code formation') }} <span class="text-red-500">*</span></label>
                    <input type="text" id="code" name="code" class="ec-input uppercase @error('code') is-invalid @enderror"
                           value="{{ old('code') }}" placeholder="{{ __('Ex : DE-A1') }}" required>
                    @error('code') <p class="ec-error">{{ $message }}</p> @enderror
                    <p class="ec-help">{{ __('Unique au sein du centre (ex : DE-A1).') }}</p>
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="ec-label">{{ __('Description du programme') }}</label>
                    <textarea id="description" name="description" rows="4" class="ec-textarea @error('description') is-invalid @enderror"
                              placeholder="{{ __('Objectifs pédagogiques, public visé, prérequis…') }}">{{ old('description') }}</textarea>
                    @error('description') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-ui.card>

        {{-- Bloc 3 : Durée, tarification & statut --}}
        <x-ui.card>
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="flex items-center gap-2 text-base font-bold text-brand-700 dark:text-brand-300">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-100 text-xs font-extrabold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">3</span>
                    <x-icon name="clock" class="h-5 w-5" />
                    {{ __('Durée, tarification & statut') }}
                </h2>
            </div>
            <div class="grid gap-4 p-5 sm:grid-cols-3">
                <div>
                    <label for="duration_hours" class="ec-label">{{ __('Volume horaire (heures)') }}</label>
                    <input type="number" id="duration_hours" name="duration_hours" min="0" step="1" class="ec-input @error('duration_hours') is-invalid @enderror"
                           value="{{ old('duration_hours') }}" placeholder="{{ __('Ex : 120') }}">
                    @error('duration_hours') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="duration_weeks" class="ec-label">{{ __('Durée (semaines)') }}</label>
                    <input type="number" id="duration_weeks" name="duration_weeks" min="0" step="1" class="ec-input @error('duration_weeks') is-invalid @enderror"
                           value="{{ old('duration_weeks') }}" placeholder="{{ __('Ex : 8') }}">
                    @error('duration_weeks') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="price" class="ec-label">{{ __('Prix / Tarif') }}</label>
                    <input type="number" id="price" name="price" min="0" step="0.01" class="ec-input @error('price') is-invalid @enderror"
                           value="{{ old('price') }}" placeholder="{{ __('Ex : 450.00') }}">
                    @error('price') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="currency" class="ec-label">{{ __('Devise') }}</label>
                    <select id="currency" name="currency" class="ec-select @error('currency') is-invalid @enderror">
                        <option value="MAD" @selected(old('currency', 'MAD') === 'MAD')>{{ __('MAD (DH)') }}</option>
                        <option value="EUR" @selected(old('currency') === 'EUR')>{{ __('EUR (€)') }}</option>
                        <option value="USD" @selected(old('currency') === 'USD')>USD ($)</option>
                        <option value="GBP" @selected(old('currency') === 'GBP')>{{ __('GBP (£)') }}</option>
                        <option value="CHF" @selected(old('currency') === 'CHF')>{{ __('CHF (CHF)') }}</option>
                    </select>
                    @error('currency') <p class="ec-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="status" class="ec-label">{{ __('Statut de la formation') }} <span class="text-red-500">*</span></label>
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
            <a href="{{ route('formations.index') }}" class="ec-btn-secondary">{{ __('Annuler') }}</a>
            <button type="submit" class="ec-btn-primary px-6" id="btn-submit-formation">
                <x-icon name="check" class="h-4 w-4" />
                {{ __('Enregistrer la formation') }}
            </button>
        </div>
    </form>
</div>
@endsection
