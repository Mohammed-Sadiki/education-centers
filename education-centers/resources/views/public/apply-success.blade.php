@extends('layouts.public')
@section('title', __('Candidature envoyée'))

@section('content')
<div class="mx-auto max-w-lg px-4 py-20 text-center">
    <h1 class="text-3xl font-semibold">{{ __('Candidature envoyée') }}</h1>
    <p class="mt-3 text-slate-500">{{ __('Votre dossier a bien été transmis. Le centre vous recontactera.') }}</p>
    <a href="{{ route('home') }}" class="ec-btn-primary mt-8">{{ __('Retour à l’accueil') }}</a>
</div>
@endsection
