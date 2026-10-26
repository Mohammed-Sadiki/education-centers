@extends('layouts.public')
@section('title', $center->translated('name'))

@section('content')
<div class="mx-auto max-w-6xl px-4 py-12">
    <p class="text-sm text-slate-500"><a href="{{ route('public.centers') }}" class="hover:text-brand-700">{{ __('Centres') }}</a> / {{ $center->translated('name') }}</p>
    <h1 class="mt-2 text-3xl font-semibold">{{ $center->translated('name') }}</h1>
    <p class="mt-2 text-slate-500">{{ $center->city }} · {{ $center->address }}</p>
    @if($center->phone || $center->email)
        <p class="mt-2 text-sm text-slate-600">{{ $center->phone }} {{ $center->email }}</p>
    @endif

    <h2 class="mt-10 text-xl font-semibold">{{ __('Formations') }}</h2>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        @forelse($formations as $formation)
            <article class="ec-card p-5">
                <h3 class="font-semibold">{{ $formation->translated('name') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $formation->duration_hours }} h · {{ format_price((float) $formation->price, $formation->currency) }}</p>
                <a href="{{ route('public.formations.show', $formation) }}" class="ec-btn-secondary ec-btn-sm mt-4">{{ __('Voir') }}</a>
            </article>
        @empty
            <p class="text-sm text-slate-500">{{ __('Aucune formation active.') }}</p>
        @endforelse
    </div>
</div>
@endsection
