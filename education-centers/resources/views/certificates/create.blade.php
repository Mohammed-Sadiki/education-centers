@extends('layouts.app')
@section('title', __('Nouveau certificat'))

@section('content')
<x-ui.page-header :title="__('Générer un certificat')" />
<form method="POST" action="{{ route('certificates.store') }}" class="ec-card max-w-xl space-y-4 p-5">
    @csrf
    @if(auth()->user()->isSuperAdmin())
        <select name="center_id" class="ec-select" required>
            @foreach($centers as $c)
                <option value="{{ $c->id }}" @selected($centerId==$c->id)>{{ $c->translated('name') }}</option>
            @endforeach
        </select>
    @endif
    <div>
        <label class="ec-label">{{ __('Étudiant') }}</label>
        <select name="student_id" class="ec-select" required>
            @foreach($students as $student)
                <option value="{{ $student->id }}" @selected($preselectedStudentId==$student->id)>{{ $student->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="ec-label">{{ __('Type') }}</label>
        <select name="type" class="ec-select" required>
            @foreach(['enrollment'=>"Attestation d'inscription",'completion'=>'Certificat de formation','attendance'=>'Attestation de présence','grades_report'=>'Relevé de notes','receipt'=>'Reçu de paiement','other'=>'Autre'] as $k=>$v)
                <option value="{{ $k }}">{{ __($v) }}</option>
            @endforeach
        </select>
    </div>
    <button class="ec-btn-primary">{{ __('Générer') }}</button>
</form>
@endsection
