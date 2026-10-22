@extends('layouts.app')

@section('title', __('Tableau de bord') . ' — ' . config('app.name'))

@section('content')
    <x-ui.page-header title="Bienvenue, {{ auth()->user()->name }}" description="Espace élève — {{ auth()->user()->center?->name }}" />

    @php $avg = $grades->whereNotNull('grade')->avg('grade'); @endphp
    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat :label="__('Présences')" :value="$presentCount" icon="check" tone="success" />
        <x-ui.stat :label="__('Absences')" :value="$absentCount" icon="x" tone="danger" />
        <x-ui.stat :label="__('Retards')" :value="$lateCount" icon="clock" tone="warning" />
        <x-ui.stat :label="__('Moyenne')" :value="$avg !== null ? format_number($avg, 2) : '—'" icon="chart" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card>
            <h2 class="mb-4 font-semibold">{{ __('Ma formation') }}</h2>
            <ul class="space-y-3 text-sm">
                @forelse($enrollments as $e)
                    <li>
                        <p class="font-medium">{{ $e->formation?->name }}</p>
                        <p class="text-slate-500">{{ __('Classe :') }} {{ $e->classRoom?->name ?? __('Non affectée') }}</p>
                        <x-status-badge type="enrollment" :status="$e->status" />
                    </li>
                @empty
                    <li class="text-slate-500">{{ __('Aucune inscription.') }}</li>
                @endforelse
            </ul>
        </x-ui.card>
        <x-ui.card>
            <h2 class="mb-4 font-semibold">{{ __('Paiements & certificats') }}</h2>
            <p class="mb-2 text-xs font-medium uppercase text-slate-500">{{ __('Paiements récents') }}</p>
            <ul class="mb-4 space-y-1 text-sm">
                @forelse($payments as $p)
                    <li>{{ format_date($p->payment_date, 'd/m/Y') }} · {{ format_number($p->amount, 2) }} {{ $p->currency }}</li>
                @empty
                    <li class="text-slate-500">{{ __('Aucun paiement.') }}</li>
                @endforelse
            </ul>
            <p class="mb-2 text-xs font-medium uppercase text-slate-500">{{ __('Certificats') }}</p>
            <ul class="space-y-1 text-sm">
                @forelse($certificates as $c)
                    <li>{{ $c->certificate_number }} · {{ $c->type_label }}</li>
                @empty
                    <li class="text-slate-500">{{ __('Aucun certificat.') }}</li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
@endsection
