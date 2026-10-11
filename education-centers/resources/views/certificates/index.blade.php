@extends('layouts.app')
@section('title', 'Certificats')

@section('content')
<x-ui.page-header title="Certificats" description="Attestations et documents officiels.">
    @can('create', \App\Models\Certificate::class)
        <a href="{{ route('certificates.create') }}" class="ec-btn-primary"><x-icon name="plus" class="h-4 w-4" /> Générer</a>
    @endcan
</x-ui.page-header>
<x-ui.card class="mb-4">
    <form method="GET" class="flex max-w-sm gap-2">
        <select name="type" class="ec-select">
            <option value="">Tous les types</option>
            @foreach(['enrollment'=>'Inscription','completion'=>'Formation','attendance'=>'Présence','grades_report'=>'Notes','receipt'=>'Reçu','other'=>'Autre'] as $k=>$v)
                <option value="{{ $k }}" @selected(request('type')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <button class="ec-btn-secondary">Filtrer</button>
    </form>
</x-ui.card>
<div class="ec-table-wrap">
    <table class="ec-table">
        <thead><tr><th>Numéro</th><th>Type</th><th>Étudiant</th><th>Formation</th><th>Date</th><th></th></tr></thead>
        <tbody>
            @forelse($certificates as $certificate)
                <tr>
                    <td class="font-mono text-xs">{{ $certificate->certificate_number }}</td>
                    <td>{{ $certificate->type_label }}</td>
                    <td>{{ $certificate->student?->full_name }}</td>
                    <td>{{ $certificate->formation?->name ?? '—' }}</td>
                    <td>{{ optional($certificate->issued_date)->format('d/m/Y') }}</td>
                    <td class="text-right">
                        @if(auth()->user()->isStudent())
                            <a href="{{ route('student.certificates.download', $certificate) }}" class="ec-btn-secondary ec-btn-sm">Télécharger</a>
                        @else
                            <a href="{{ route('certificates.show', $certificate) }}" class="ec-btn-ghost ec-btn-sm">Voir</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-ui.empty title="Aucun certificat" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($certificates->hasPages())<div class="mt-4">{{ $certificates->links() }}</div>@endif
@endsection
