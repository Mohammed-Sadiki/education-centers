@extends('layouts.public')
@section('title', __('Formations') . ' — ' . config('app.name'))

@section('content')
<div class="mx-auto max-w-6xl px-4 py-12">
    <h1 class="text-3xl font-semibold">{{ __('Formations') }}</h1>
    <form method="GET" class="mt-6 max-w-sm">
        <label class="ec-label" for="center_id">{{ __('Centre') }}</label>
        <select name="center_id" id="center_id" class="ec-select" onchange="this.form.submit()">
            <option value="">{{ __('Tous les centres') }}</option>
            @foreach($centers as $center)
                <option value="{{ $center->id }}" @selected(request('center_id') == $center->id)>{{ $center->translated('name') }}</option>
            @endforeach
        </select>
    </form>
    <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse($formations as $formation)
            <article class="ec-card p-5">
                <h2 class="font-semibold">{{ $formation->translated('name') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $formation->center?->translated('name') }}</p>
                <p class="mt-3 text-sm">{{ $formation->duration_hours }} h · {{ $formation->duration_weeks }} {{ __('semaines') }}</p>
                <p class="mt-1 font-medium">{{ format_price((float) $formation->price, $formation->currency) }}</p>
                <x-status-badge :status="$formation->status" />
                <a href="{{ route('public.formations.show', $formation) }}" class="ec-btn-primary ec-btn-sm mt-4">{{ __('Détail') }}</a>
            </article>
        @empty
            <x-ui.empty :title="__('Aucune formation')" />
        @endforelse
    </div>
    <div class="mt-8">{{ $formations->links() }}</div>
</div>
@endsection
