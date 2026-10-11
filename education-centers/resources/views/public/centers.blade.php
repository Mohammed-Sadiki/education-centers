@extends('layouts.public')
@section('title', 'Centres — Education Centers')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-12">
    <h1 class="text-3xl font-semibold">Nos centres</h1>
    <p class="mt-2 text-slate-500">Choisissez un campus et découvrez ses formations.</p>
    <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse($centers as $center)
            <article class="ec-card p-5">
                <h2 class="font-semibold">{{ $center->name }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $center->city }} · {{ $center->code }}</p>
                <p class="mt-3 text-sm text-slate-600">{{ $center->address }}</p>
                <p class="mt-3 text-xs text-slate-500">{{ $center->formations_count }} formations · {{ $center->teachers_count }} professeurs · {{ $center->students_count }} étudiants</p>
                <a href="{{ route('public.centers.show', $center) }}" class="ec-btn-primary ec-btn-sm mt-4">Détail</a>
            </article>
        @empty
            <x-ui.empty title="Aucun centre actif" />
        @endforelse
    </div>
</div>
@endsection
