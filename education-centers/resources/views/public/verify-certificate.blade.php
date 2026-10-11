@extends('layouts.public')
@section('title', 'Vérifier un document')

@section('content')
<div class="mx-auto max-w-lg px-4 py-12">
    <h1 class="text-3xl font-semibold">Vérifier un document</h1>
    <form method="GET" class="ec-card mt-6 space-y-4 p-6">
        <label class="ec-label" for="token">Jeton de vérification</label>
        <input class="ec-input" id="token" name="token" value="{{ $token }}" required>
        <button class="ec-btn-primary w-full">Vérifier</button>
    </form>
    @if($token)
        @if($certificate)
            <div class="ec-card mt-6 p-6">
                <p class="font-semibold text-emerald-700">Document authentique</p>
                <p class="mt-2 text-sm">{{ $certificate->certificate_number }}</p>
                <p class="text-sm text-slate-500">{{ $certificate->student?->full_name }} · {{ $certificate->type_label }}</p>
            </div>
        @else
            <p class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">Aucun certificat valide pour ce jeton.</p>
        @endif
    @endif
</div>
@endsection
