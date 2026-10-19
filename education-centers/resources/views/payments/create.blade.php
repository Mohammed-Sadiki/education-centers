@extends('layouts.app')
@section('title', 'Nouveau paiement')

@section('content')
<x-ui.page-header title="Enregistrer un paiement" />
<form method="POST" action="{{ route('payments.store') }}" class="ec-card max-w-2xl space-y-4 p-5">
    @csrf
    @if(auth()->user()->isSuperAdmin())
        <div>
            <label class="ec-label">Centre</label>
            <select name="center_id" class="ec-select" required>
                @foreach($centers as $c)
                    <option value="{{ $c->id }}" @selected($centerId==$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div>
        <label class="ec-label">Étudiant <span class="text-red-600">*</span></label>
        <select name="student_id" class="ec-select" required>
            <option value="">Sélectionner</option>
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
        <label class="ec-label">Formation</label>
        <select name="formation_id" class="ec-select">
            <option value="">—</option>
            @foreach($formations as $f)
                <option value="{{ $f->id }}" @selected(old('formation_id')==$f->id)>{{ $f->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="ec-label">Montant <span class="text-red-600">*</span></label>
            <input type="number" step="0.01" name="amount" class="ec-input" value="{{ old('amount') }}" required>
        </div>
        <div>
            <label class="ec-label">Devise</label>
            <input name="currency" class="ec-input" value="{{ old('currency', default_currency()) }}" required>
        </div>
        <div>
            <label class="ec-label">Date</label>
            <input type="date" name="payment_date" class="ec-input" value="{{ old('payment_date', now()->toDateString()) }}" required>
        </div>
        <div>
            <label class="ec-label">Méthode</label>
            <select name="method" class="ec-select" required>
                <option value="cash">Espèces</option>
                <option value="card">Carte</option>
                <option value="bank_transfer">Virement</option>
                <option value="other">Autre</option>
            </select>
        </div>
    </div>
    <div>
        <label class="ec-label">Notes</label>
        <textarea name="notes" class="ec-textarea" rows="3">{{ old('notes') }}</textarea>
    </div>
    <button class="ec-btn-primary">Enregistrer</button>
</form>
@endsection
