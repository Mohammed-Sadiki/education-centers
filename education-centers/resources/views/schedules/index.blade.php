@extends('layouts.app')
@section('title', __('Emplois du temps'))

@section('content')
<x-ui.page-header :title="__('Planning des séances')" :description="__('Cours, examens et ateliers.')">
    <a href="{{ route('schedules.create') }}" class="ec-btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('Programmer une séance') }}</a>
</x-ui.page-header>
<x-ui.card class="mb-4">
    <form method="GET" action="{{ route('schedules.index') }}" class="grid gap-3 md:grid-cols-6">
        @if(auth()->user()->isSuperAdmin())
            <select name="center_id" class="ec-select">
                <option value="">{{ __('Tous les centres') }}</option>
                @foreach($centers as $c)
                    <option value="{{ $c->id }}" @selected(request('center_id')==$c->id)>{{ $c->translated('name') }}</option>
                @endforeach
            </select>
        @endif
        <input type="date" name="date" class="ec-input" value="{{ request('date') }}">
        <select name="class_id" class="ec-select">
            <option value="">{{ __('Toutes les classes') }}</option>
            @foreach($classes as $cl)
                <option value="{{ $cl->id }}" @selected(request('class_id')==$cl->id)>{{ $cl->name }}</option>
            @endforeach
        </select>
        <select name="status" class="ec-select">
            <option value="">{{ __('Tous') }}</option>
            <option value="scheduled" @selected(request('status')==='scheduled')>{{ __('Planifié') }}</option>
            <option value="completed" @selected(request('status')==='completed')>{{ __('Terminé') }}</option>
            <option value="cancelled" @selected(request('status')==='cancelled')>{{ __('Annulé') }}</option>
        </select>
        <button class="ec-btn-secondary">{{ __('Filtrer') }}</button>
    </form>
</x-ui.card>
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse($schedules as $s)
        <article class="ec-card p-5">
            <p class="text-sm font-semibold text-brand-700">{{ substr($s->start_time, 0, 5) }} — {{ substr($s->end_time, 0, 5) }}</p>
            <h2 class="mt-2 font-semibold">{{ $s->title ?: ($s->classRoom?->formation?->name ?? __('Séance')) }}</h2>
            <p class="text-sm text-slate-500">{{ $s->classRoom?->name }}</p>
            <p class="mt-3 text-sm">{{ $s->teacher?->full_name }}</p>
            <p class="text-sm text-slate-500">{{ $s->room?->code }} · {{ $s->formatted_date }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <x-status-badge type="schedule_type" :status="$s->type" />
                <x-status-badge type="schedule" :status="$s->status" />
            </div>
            <a href="{{ route('schedules.show', $s) }}" class="ec-btn-ghost ec-btn-sm mt-4">{{ __('Voir') }}</a>
        </article>
    @empty
        <div class="md:col-span-2"><x-ui.empty :title="__('Aucune séance')" /></div>
    @endforelse
</div>
@if($schedules->hasPages())<div class="mt-6">{{ $schedules->links() }}</div>@endif
@endsection
