@extends('layouts.public')
@section('title', 'Candidature envoyée')

@section('content')
<div class="mx-auto max-w-lg px-4 py-20 text-center">
    <h1 class="text-3xl font-semibold">Candidature envoyée</h1>
    <p class="mt-3 text-slate-500">Votre dossier a bien été transmis. Le centre vous recontactera.</p>
    <a href="{{ route('home') }}" class="ec-btn-primary mt-8">Retour à l’accueil</a>
</div>
@endsection
