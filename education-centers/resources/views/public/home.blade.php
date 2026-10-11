@extends('layouts.public')

@section('title', 'Education Centers — Votre avenir commence ici')
@section('description', 'Plateforme de gestion et de découverte des centres de formation.')

@section('content')
<section class="bg-slate-50">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 lg:grid-cols-2 lg:items-center lg:py-24">
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-brand-700">Education Centers</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white sm:text-5xl">Votre avenir commence ici.</h1>
            <p class="mt-4 max-w-xl text-lg text-slate-600 dark:text-slate-300">Découvrez nos centres, nos formations et déposez votre candidature en quelques minutes.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('public.formations') }}" class="ec-btn-primary">Découvrir nos formations</a>
                <a href="{{ route('public.apply') }}" class="ec-btn-secondary">S’inscrire</a>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-3">
            <div class="ec-card p-5 text-center">
                <p class="text-3xl font-semibold text-brand-700">{{ $stats['centers'] }}</p>
                <p class="mt-1 text-sm text-slate-500">Centres</p>
            </div>
            <div class="ec-card p-5 text-center">
                <p class="text-3xl font-semibold text-brand-700">{{ $stats['formations'] }}</p>
                <p class="mt-1 text-sm text-slate-500">Formations</p>
            </div>
            <div class="ec-card p-5 text-center">
                <p class="text-3xl font-semibold text-brand-700">{{ $stats['students'] }}</p>
                <p class="mt-1 text-sm text-slate-500">Étudiants</p>
            </div>
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-16">
    <div class="mb-8 flex items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold">Nos centres</h2>
            <p class="mt-1 text-sm text-slate-500">Des campus proches de vous.</p>
        </div>
        <a href="{{ route('public.centers') }}" class="text-sm font-medium text-brand-700 hover:underline">Voir tous</a>
    </div>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse($centers as $center)
            <article class="ec-card p-5">
                <h3 class="font-semibold">{{ $center->name }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $center->city }}</p>
                <p class="mt-3 text-sm text-slate-600">{{ $center->formations_count }} formation(s)</p>
                <a href="{{ route('public.centers.show', $center) }}" class="ec-btn-secondary ec-btn-sm mt-4">Voir le centre</a>
            </article>
        @empty
            <p class="text-sm text-slate-500">Aucun centre publié pour le moment.</p>
        @endforelse
    </div>
</section>

<section class="bg-slate-50 py-16">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mb-8 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold">Formations</h2>
                <p class="mt-1 text-sm text-slate-500">Un extrait de notre catalogue actif.</p>
            </div>
            <a href="{{ route('public.formations') }}" class="text-sm font-medium text-brand-700 hover:underline">Catalogue</a>
        </div>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse($formations as $formation)
                <article class="ec-card p-5">
                    <h3 class="font-semibold">{{ $formation->name }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $formation->center?->name }}</p>
                    <p class="mt-3 text-sm">{{ $formation->duration_hours }} h · {{ number_format((float) $formation->price, 2, ',', ' ') }} {{ $formation->currency }}</p>
                    <a href="{{ route('public.formations.show', $formation) }}" class="ec-btn-secondary ec-btn-sm mt-4">Détail</a>
                </article>
            @empty
                <p class="text-sm text-slate-500">Aucune formation publiée pour le moment.</p>
            @endforelse
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-16">
    <h2 class="text-2xl font-semibold">Pourquoi nous choisir</h2>
    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <div class="ec-card p-5">
            <h3 class="font-semibold">Multi-centres</h3>
            <p class="mt-2 text-sm text-slate-600">Un même standard pédagogique, plusieurs villes.</p>
        </div>
        <div class="ec-card p-5">
            <h3 class="font-semibold">Suivi clair</h3>
            <p class="mt-2 text-sm text-slate-600">Présences, notes, documents et certificats centralisés.</p>
        </div>
        <div class="ec-card p-5">
            <h3 class="font-semibold">Candidature simple</h3>
            <p class="mt-2 text-sm text-slate-600">Déposez votre dossier en ligne, nous le traitons ensuite.</p>
        </div>
    </div>
</section>

@if($announcements->isNotEmpty())
<section class="bg-slate-50 py-16">
    <div class="mx-auto max-w-6xl px-4">
        <h2 class="text-2xl font-semibold">Actualités</h2>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            @foreach($announcements as $a)
                <a href="{{ route('public.announcements.show', $a->slug) }}" class="ec-card block p-5 hover:border-brand-200">
                    <p class="text-xs text-slate-500">{{ optional($a->published_at)->format('d/m/Y') }}</p>
                    <h3 class="mt-2 font-semibold">{{ $a->title }}</h3>
                    <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit(strip_tags($a->content), 140) }}</p>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="mx-auto max-w-6xl px-4 py-16 text-center">
    <h2 class="text-2xl font-semibold">Prêt à rejoindre un centre ?</h2>
    <p class="mt-2 text-slate-500">Déposez votre candidature. L’équipe du centre vous recontactera.</p>
    <a href="{{ route('public.apply') }}" class="ec-btn-primary mt-6">S’inscrire</a>
</section>
@endsection
