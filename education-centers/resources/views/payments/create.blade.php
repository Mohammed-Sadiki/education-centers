@extends('layouts.app')
@section('title', __('Nouveau paiement'))

@section('content')
<x-ui.page-header :title="__('Enregistrer un paiement')" />
<form method="POST" action="{{ route('payments.store') }}" class="ec-card max-w-2xl space-y-4 p-5">
    @csrf
    @if(auth()->user()->isSuperAdmin())
        <div>
            <label class="ec-label">{{ __('Centre') }}</label>
            <select name="center_id" class="ec-select" required>
                @foreach($centers as $c)
                    <option value="{{ $c->id }}" @selected($centerId==$c->id)>{{ $c->translated('name') }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div>
        <label class="ec-label">{{ __('Étudiant') }} <span class="text-red-600">*</span></label>
        <select name="student_id" class="ec-select" required>
            <option value="">{{ __('Sélectionner') }}</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" @selected(old('student_id', $preselectedStudentId)==$student->id)>{{ $student->full_name }}</option>
            @endforeach
        </select>
        @error('student_id')<p class="ec-error">{{ $message }}</p>@enderror
    </div>
    @if($preselectedEnrollmentId)
        <input type="hidden" name="enrollment_id" value="{{ $preselectedEnrollmentId }}">
    @endif
    <div>
        <label class="ec-label">{{ __('Formation') }}</label>
        <select name="formation_id" class="ec-select">
            <option value="">—</option>
            @foreach($formations as $f)
                <option value="{{ $f->id }}" @selected(old('formation_id')==$f->id)>{{ $f->translated('name') }}</option>
            @endforeach
        </select>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="ec-label">{{ __('Montant') }} <span class="text-red-600">*</span></label>
            <input type="number" step="0.01" name="amount" class="ec-input" value="{{ old('amount') }}" required>
        </div>
        <div>
            <label class="ec-label">{{ __('Devise') }}</label>
            <input name="currency" class="ec-input" value="{{ old('currency', default_currency()) }}" required>
        </div>
        <div>
            <label class="ec-label">{{ __('Date') }}</label>
            <input type="date" name="payment_date" class="ec-input" value="{{ old('payment_date', now()->toDateString()) }}" required>
        </div>
        <div>
            <label class="ec-label">{{ __('Méthode') }}</label>
            <select name="method" class="ec-select" required>
                <option value="cash">{{ __('Espèces') }}</option>
                <option value="card">{{ __('Carte') }}</option>
                <option value="bank_transfer">{{ __('Virement') }}</option>
                <option value="other">{{ __('Autre') }}</option>
            </select>
        </div>
    </div>
    <div>
        <label class="ec-label">{{ __('Notes') }}</label>
        <textarea name="notes" class="ec-textarea" rows="3">{{ old('notes') }}</textarea>
    </div>
    <button class="ec-btn-primary">{{ __('Enregistrer') }}</button>
</form>
@endsection
