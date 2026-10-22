@extends('layouts.app')
@section('title', __('Documents'))

@section('content')
<x-ui.page-header :title="__('Documents')" :description="__('Gestionnaire de documents étudiants.')">
    @can('create', \App\Models\Document::class)
        <a href="{{ route('documents.create') }}" class="ec-btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('Ajouter') }}</a>
    @endcan
</x-ui.page-header>
<x-ui.card class="mb-4">
    <form method="GET" class="grid gap-3 md:grid-cols-3">
        <select name="type" class="ec-select">
            <option value="">{{ __('Tous les types') }}</option>
            @foreach(['identity'=>'Identité','diploma'=>'Diplôme','certificate'=>'Certificat','attestation'=>'Attestation','administrative'=>'Administratif','other'=>'Autre'] as $k=>$v)
                <option value="{{ $k }}" @selected(request('type')===$k)>{{ __($v) }}</option>
            @endforeach
        </select>
        <button class="ec-btn-secondary">{{ __('Filtrer') }}</button>
    </form>
</x-ui.card>
<div class="ec-table-wrap">
    <table class="ec-table">
        <thead><tr><th>{{ __('Type') }}</th><th>{{ __('Titre') }}</th><th>{{ __('Étudiant') }}</th><th>{{ __('Date') }}</th><th>{{ __('Taille') }}</th><th>{{ __('Statut') }}</th><th></th></tr></thead>
        <tbody>
            @forelse($documents as $document)
                <tr>
                    <td>{{ $document->type_label }}</td>
                    <td class="font-medium">{{ $document->title }}</td>
                    <td>{{ $document->student?->full_name }}</td>
                    <td>{{ format_date($document->created_at, 'd/m/Y') }}</td>
                    <td>{{ $document->formatted_size }}</td>
                    <td><x-status-badge :status="$document->status" /></td>
                    <td class="text-right">
                        <div class="flex justify-end gap-1">
                            @if(!auth()->user()->isStudent())
                                <a href="{{ route('documents.show', $document) }}" class="ec-btn-ghost ec-btn-sm">{{ __('Consulter') }}</a>
                            @endif
                            @can('download', $document)
                                <a href="{{ auth()->user()->isStudent() ? route('student.documents.download', $document) : route('documents.download', $document) }}" class="ec-btn-secondary ec-btn-sm">{{ __('Télécharger') }}</a>
                            @endcan
                            @can('delete', $document)
                                <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('{{ __('Supprimer ce document ?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="ec-btn-danger ec-btn-sm">{{ __('Supprimer') }}</button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><x-ui.empty :title="__('Aucun document')" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($documents->hasPages())<div class="mt-4">{{ $documents->links() }}</div>@endif
@endsection
