@extends('layouts.public')
@section('title', __('Candidature'))

@section('content')
<div class="mx-auto max-w-2xl px-4 py-12" x-data="{ step: 1 }">
    <h1 class="text-3xl font-semibold">{{ __('Candidature') }}</h1>
    <p class="mt-2 text-slate-500">{{ __('Quatre étapes. Les informations sensibles (centre, formation, statut) sont validées par le serveur.') }}</p>

    <ol class="mt-6 grid grid-cols-4 gap-2 text-center text-xs font-medium">
        <li :class="step >= 1 ? 'text-brand-700' : 'text-slate-400'">{{ __('1. Identité') }}</li>
        <li :class="step >= 2 ? 'text-brand-700' : 'text-slate-400'">{{ __('2. Formation') }}</li>
        <li :class="step >= 3 ? 'text-brand-700' : 'text-slate-400'">{{ __('3. Notes') }}</li>
        <li :class="step >= 4 ? 'text-brand-700' : 'text-slate-400'">{{ __('4. Confirmation') }}</li>
    </ol>

    <form method="POST" action="{{ route('public.apply.store') }}" class="ec-card mt-8 space-y-4 p-6">
        @csrf
        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                <ul class="list-disc ps-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div x-show="step === 1" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="ec-label" for="first_name">{{ __('Prénom') }} <span class="text-red-600">*</span></label>
                    <input class="ec-input" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                </div>
                <div>
                    <label class="ec-label" for="last_name">{{ __('Nom') }} <span class="text-red-600">*</span></label>
                    <input class="ec-input" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                </div>
            </div>
            <div>
                <label class="ec-label" for="email">{{ __('Email') }} <span class="text-red-600">*</span></label>
                <input type="email" class="ec-input" id="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div>
                <label class="ec-label" for="phone">{{ __('Téléphone') }} <span class="text-red-600">*</span></label>
                <input class="ec-input" id="phone" name="phone" value="{{ old('phone') }}" required>
            </div>
            <div>
                <label class="ec-label" for="date_of_birth">{{ __('Date de naissance') }} <span class="text-red-600">*</span></label>
                <input type="date" class="ec-input" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}" required>
            </div>
            <div>
                <label class="ec-label" for="address">{{ __('Adresse') }}</label>
                <input class="ec-input" id="address" name="address" value="{{ old('address') }}">
            </div>
            <button type="button" class="ec-btn-primary" x-on:click="step = 2">{{ __('Continuer') }}</button>
        </div>

        <div x-show="step === 2" x-cloak class="space-y-4">
            <div>
                <label class="ec-label" for="center_id">{{ __('Centre') }} <span class="text-red-600">*</span></label>
                <select class="ec-select" id="center_id" name="center_id" required>
                    <option value="">{{ __('Sélectionner') }}</option>
                    @foreach($centers as $center)
                        <option value="{{ $center->id }}" @selected(old('center_id', $preselectedCenterId) == $center->id)>{{ $center->translated('name') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ec-label" for="formation_id">{{ __('Formation') }} <span class="text-red-600">*</span></label>
                <select class="ec-select" id="formation_id" name="formation_id" required>
                    <option value="">{{ __('Sélectionner') }}</option>
                    @foreach($formations as $formation)
                        <option value="{{ $formation->id }}" data-center="{{ $formation->center_id }}" @selected(old('formation_id', $preselectedFormationId) == $formation->id)>
                            {{ $formation->translated('name') }} — {{ $formation->center?->translated('name') }}
                        </option>
                    @endforeach
                </select>
                <p class="ec-help">{{ __('Le centre et la formation doivent correspondre. Le serveur vérifie cette cohérence.') }}</p>
            </div>
            <div class="flex gap-2">
                <button type="button" class="ec-btn-secondary" x-on:click="step = 1">{{ __('Retour') }}</button>
                <button type="button" class="ec-btn-primary" x-on:click="step = 3">{{ __('Continuer') }}</button>
            </div>
        </div>

        <div x-show="step === 3" x-cloak class="space-y-4">
            <div>
                <label class="ec-label" for="notes">{{ __('Notes / documents éventuels') }}</label>
                <textarea class="ec-textarea" id="notes" name="notes" rows="4" placeholder="{{ __('Précisez tout élément utile. Les pièces officielles seront demandées par le centre.') }}">{{ old('notes') }}</textarea>
            </div>
            <div class="flex gap-2">
                <button type="button" class="ec-btn-secondary" x-on:click="step = 2">{{ __('Retour') }}</button>
                <button type="button" class="ec-btn-primary" x-on:click="step = 4">{{ __('Continuer') }}</button>
            </div>
        </div>

        <div x-show="step === 4" x-cloak class="space-y-4">
            <p class="text-sm text-slate-600">{{ __('Vérifiez vos informations puis envoyez la candidature. Un compte étudiant sera créé si l’email n’existe pas encore.') }}</p>
            <div class="flex gap-2">
                <button type="button" class="ec-btn-secondary" x-on:click="step = 3">{{ __('Retour') }}</button>
                <button type="submit" class="ec-btn-primary">{{ __('Confirmer et envoyer') }}</button>
            </div>
        </div>
    </form>
</div>
@endsection
