@extends('layouts.app')
@section('title', $document->title)

@section('content')
<x-ui.page-header title="{{ $document->title }}" :description="$document->student?->full_name">
    @can('download', $document)
        <a href="{{ auth()->user()->isStudent() ? route('student.documents.download', $document) : route('documents.download', $document) }}" class="ec-btn-primary">{{ __('Télécharger') }}</a>
    @endcan
</x-ui.page-header>
<x-ui.card>
    <dl class="grid gap-3 text-sm sm:grid-cols-2">
        <div><dt class="text-slate-500">{{ __('Type') }}</dt><dd>{{ $document->type_label }}</dd></div>
        <div><dt class="text-slate-500">{{ __('Fichier') }}</dt><dd>{{ $document->original_name }} ({{ $document->formatted_size }})</dd></div>
        <div><dt class="text-slate-500">{{ __('Statut') }}</dt><dd><x-status-badge :status="$document->status" /></dd></div>
        <div><dt class="text-slate-500">{{ __('Déposé par') }}</dt><dd>{{ $document->uploader?->name }}</dd></div>
        <div class="sm:col-span-2"><dt class="text-slate-500">{{ __('Description') }}</dt><dd>{{ $document->description ?: '—' }}</dd></div>
    </dl>
</x-ui.card>
@endsection
