@extends('layouts.app')
@section('title', 'Nouvelle facture')

@section('content')
<x-ui.page-header title="Nouvelle facture" />
<form method="POST" action="{{ route('invoices.store') }}" class="ec-card max-w-2xl space-y-4 p-5">
    @csrf
    @if(auth()->user()->isSuperAdmin())
        <select name="center_id" class="ec-select" required>
            @foreach($centers as $c)
                <option value="{{ $c->id }}" @selected($centerId==$c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    @endif
    <div>
        <label class="ec-label">Étudiant <span class="text-red-600">*</span></label>
        <select name="student_id" class="ec-select" required>
            <option value="">Sélectionner</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}">{{ $student->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="ec-label">Formation</label>
        <select name="formation_id" class="ec-select">
            <option value="">—</option>
            @foreach($formations as $f)
                <option value="{{ $f->id }}">{{ $f->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="ec-label">Montant total</label>
            <input type="number" step="0.01" name="total_amount" class="ec-input" required>
        </div>
        <div>
            <label class="ec-label">Devise</label>
            <input name="currency" class="ec-input" value="EUR" required>
        </div>
        <div>
            <label class="ec-label">Date</label>
            <input type="date" name="invoice_date" class="ec-input" value="{{ now()->toDateString() }}" required>
        </div>
        <div>
            <label class="ec-label">Échéance</label>
            <input type="date" name="due_date" class="ec-input">
        </div>
    </div>
    <textarea name="notes" class="ec-textarea" rows="3" placeholder="Notes"></textarea>
    <button class="ec-btn-primary">Créer</button>
</form>
@endsection
