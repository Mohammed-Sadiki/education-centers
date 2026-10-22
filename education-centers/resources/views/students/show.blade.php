@extends('layouts.app')
@section('title', "Dossier Élève {$student->student_number}")

@section('content')
@php
    $enrollments = $student->enrollments()->with(['formation','classRoom'])->latest()->get();
    $attendances = $student->attendances()->latest('date')->take(20)->get();
    $grades = $student->grades()->with('evaluation')->latest()->take(20)->get();
    $payments = $student->payments()->latest('payment_date')->take(20)->get();
    $documents = $student->documents()->latest()->take(20)->get();
    $certificates = $student->certificates()->latest('issued_date')->take(20)->get();
    $currentEnrollment = $enrollments->firstWhere('status', 'accepted') ?? $enrollments->first();
@endphp

<x-ui.page-header title="{{ $student->full_name }}" :description="'N° '.$student->student_number.' · '.($student->center?->name ?? '')">
    <a href="{{ route('students.index') }}" class="ec-btn-secondary">{{ __('Retour') }}</a>
    @can('update', $student)
        <a href="{{ route('students.edit', $student) }}" class="ec-btn-primary" id="btn-edit-student">{{ __('Modifier') }}</a>
    @endcan
    @can('toggleStatus', $student)
        <form method="POST" action="{{ route('students.toggle-status', $student) }}">
            @csrf @method('PATCH')
            <button type="submit" class="ec-btn-warning" id="btn-toggle-status" onclick="return confirm('{{ __('Confirmer le changement de statut ?') }}')">
                {{ $student->isActive() ? __('Désactiver') : __('Activer') }}
            </button>
        </form>
    @endcan
</x-ui.page-header>

<div class="mb-6 flex items-center gap-3">
    <x-ui.avatar :name="$student->full_name" size="lg" />
    <div>
        <x-status-badge :status="$student->status" />
        <p class="mt-1 text-sm text-slate-500">{{ $student->user?->email }}</p>
    </div>
</div>

<div x-data="{ tab: 'info' }">
    <div class="mb-4 flex flex-wrap gap-1 border-b border-slate-200 dark:border-slate-800">
        @foreach(['info'=>'Informations','formation'=>'Formation','classe'=>'Classe','inscriptions'=>'Inscriptions','presences'=>'Présences','notes'=>'Notes','paiements'=>'Paiements','documents'=>'Documents','certificats'=>'Certificats'] as $key=>$label)
            <button type="button" class="px-3 py-2 text-sm font-medium" :class="tab === '{{ $key }}' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-slate-500'" x-on:click="tab = '{{ $key }}'">{{ __($label) }}</button>
        @endforeach
    </div>

    <div x-show="tab === 'info'" class="ec-card p-5 text-sm">
        <dl class="grid gap-3 sm:grid-cols-2">
            <div><dt class="text-slate-500">{{ __('Nom') }}</dt><dd class="font-medium">{{ $student->full_name }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Naissance') }}</dt><dd>{{ format_date($student->date_of_birth, 'd/m/Y') ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Téléphone') }}</dt><dd>{{ $student->phone ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Ville') }}</dt><dd>{{ $student->city ?: '—' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-slate-500">{{ __('Adresse') }}</dt><dd>{{ $student->address ?: '—' }}</dd></div>
        </dl>
    </div>
    <div x-show="tab === 'formation'" x-cloak class="ec-card p-5 text-sm">
        <p class="font-medium">{{ $currentEnrollment?->formation?->name ?? __('Aucune formation') }}</p>
        <p class="text-slate-500">{{ $currentEnrollment?->formation?->code }}</p>
    </div>
    <div x-show="tab === 'classe'" x-cloak class="ec-card p-5 text-sm">
        <p>{{ $currentEnrollment?->classRoom?->name ?? __('Non affectée') }}</p>
    </div>
    <div x-show="tab === 'inscriptions'" x-cloak>
        <div class="ec-table-wrap"><table class="ec-table"><thead><tr><th>N°</th><th>{{ __('Formation') }}</th><th>{{ __('Statut') }}</th></tr></thead><tbody>
            @forelse($enrollments as $e)
                <tr><td>{{ $e->registration_number }}</td><td>{{ $e->formation?->name }}</td><td><x-status-badge type="enrollment" :status="$e->status" /></td></tr>
            @empty
                <tr><td colspan="3"><x-ui.empty :title="__('Aucune inscription')" /></td></tr>
            @endforelse
        </tbody></table></div>
    </div>
    <div x-show="tab === 'presences'" x-cloak>
        <div class="ec-table-wrap"><table class="ec-table"><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Statut') }}</th></tr></thead><tbody>
            @forelse($attendances as $a)
                <tr><td>{{ format_date($a->date, 'd/m/Y') }}</td><td><x-status-badge type="attendance" :status="$a->status" /></td></tr>
            @empty
                <tr><td colspan="2"><x-ui.empty :title="__('Aucune présence')" /></td></tr>
            @endforelse
        </tbody></table></div>
    </div>
    <div x-show="tab === 'notes'" x-cloak>
        <div class="ec-table-wrap"><table class="ec-table"><thead><tr><th>{{ __('Évaluation') }}</th><th>{{ __('Note') }}</th></tr></thead><tbody>
            @forelse($grades as $g)
                <tr><td>{{ $g->evaluation?->title }}</td><td>{{ $g->is_absent ? 'Absent' : ($g->grade ?? '—') }}</td></tr>
            @empty
                <tr><td colspan="2"><x-ui.empty :title="__('Aucune note')" /></td></tr>
            @endforelse
        </tbody></table></div>
    </div>
    <div x-show="tab === 'paiements'" x-cloak>
        <div class="ec-table-wrap"><table class="ec-table"><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Montant') }}</th><th>{{ __('Statut') }}</th></tr></thead><tbody>
            @forelse($payments as $p)
                <tr><td>{{ format_date($p->payment_date, 'd/m/Y') }}</td><td>{{ format_number((float) $p->amount, 2) }} {{ $p->currency }}</td><td><x-status-badge type="payment" :status="$p->status" /></td></tr>
            @empty
                <tr><td colspan="3"><x-ui.empty :title="__('Aucun paiement')" /></td></tr>
            @endforelse
        </tbody></table></div>
    </div>
    <div x-show="tab === 'documents'" x-cloak>
        <ul class="space-y-2 text-sm">
            @forelse($documents as $d)
                <li class="ec-card p-3">{{ $d->title }} · {{ $d->type_label }}</li>
            @empty
                <x-ui.empty :title="__('Aucun document')" />
            @endforelse
        </ul>
    </div>
    <div x-show="tab === 'certificats'" x-cloak>
        <ul class="space-y-2 text-sm">
            @forelse($certificates as $c)
                <li class="ec-card p-3">{{ $c->certificate_number }} · {{ $c->type_label }}</li>
            @empty
                <x-ui.empty :title="__('Aucun certificat')" />
            @endforelse
        </ul>
    </div>
</div>
@endsection
