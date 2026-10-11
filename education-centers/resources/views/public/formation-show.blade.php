@extends('layouts.public')
@section('title', $formation->name)

@section('content')
<div class="mx-auto max-w-4xl px-4 py-12">
    <p class="text-sm text-slate-500"><a href="{{ route('public.formations') }}" class="hover:text-brand-700">Formations</a> / {{ $formation->name }}</p>
    <h1 class="mt-2 text-3xl font-semibold">{{ $formation->name }}</h1>
    <p class="mt-2 text-slate-500">{{ $formation->center?->name }} · {{ $formation->code }}</p>
    <div class="mt-6 flex flex-wrap gap-3 text-sm">
        <span class="ec-badge bg-slate-100">{{ $formation->duration_hours }} heures</span>
        <span class="ec-badge bg-slate-100">{{ $formation->duration_weeks }} semaines</span>
        <span class="ec-badge bg-brand-50 text-brand-700">{{ number_format((float) $formation->price, 2, ',', ' ') }} {{ $formation->currency }}</span>
    </div>
    @if($formation->description)
        <div class="prose mt-8 max-w-none text-slate-700 dark:text-slate-300">{{ $formation->description }}</div>
    @endif
    <h2 class="mt-10 text-xl font-semibold">Classes disponibles</h2>
    <ul class="mt-4 space-y-2">
        @forelse($formation->classes as $class)
            <li class="rounded-lg border border-slate-200 px-4 py-3 text-sm dark:border-slate-800">
                {{ $class->name }} ({{ $class->code }}) · capacité {{ $class->capacity ?? 'illimitée' }}
            </li>
        @empty
            <li class="text-sm text-slate-500">Aucune classe listée.</li>
        @endforelse
    </ul>
    <a href="{{ route('public.apply', ['formation_id' => $formation->id, 'center_id' => $formation->center_id]) }}" class="ec-btn-primary mt-8">S’inscrire à cette formation</a>
</div>
@endsection
