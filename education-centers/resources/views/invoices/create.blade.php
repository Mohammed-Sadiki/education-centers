@extends('layouts.app')
@section('title', __('Nouvelle facture'))

@section('content')
<x-ui.page-header :title="__('Nouvelle facture')" />
<form method="POST" action="{{ route('invoices.store') }}" class="ec-card max-w-2xl space-y-4 p-5">
    @csrf
    @if(auth()->user()->isSuperAdmin())
        <select name="center_id" class="ec-select" required>
            @foreach($centers as $c)
                <option value="{{ $c->id }}" @selected($centerId==$c->id)>{{ $c->translated('name') }}</option>
            @endforeach
        </select>
    @endif
    <div>
        <label class="ec-label">{{ __('Étudiant') }} <span class="text-red-600">*</span></label>
        <select name="student_id" class="ec-select" required>
            <option value="">{{ __('Sélectionner') }}</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}">{{ $student->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="ec-label">{{ __('Formation') }}</label>
        <select name="formation_id" class="ec-select">
            <option value="">—</option>
            @foreach($formations as $f)
                <option value="{{ $f->id }}">{{ $f->translated('name') }}</option>
            @endforeach
        </select>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="ec-label">{{ __('Montant total') }}</label>
            <input type="number" step="0.01" name="total_amount" class="ec-input" required>
        </div>
        <div>
            <label class="ec-label">{{ __('Devise') }}</label>
            <input name="currency" class="ec-input" value="{{ old('currency', default_currency()) }}" required>
        </div>
        <div>
            <label class="ec-label">{{ __('Date') }}</label>
            <input type="date" name="invoice_date" class="ec-input" value="{{ now()->toDateString() }}" required>
        </div>
        <div>
            <label class="ec-label">{{ __('Échéance') }}</label>
            <input type="date" name="due_date" class="ec-input">
        </div>
    </div>
    <textarea name="notes" class="ec-textarea" rows="3" placeholder="{{ __('Notes') }}"></textarea>
    <button class="ec-btn-primary">{{ __('Créer') }}</button>
</form>
@endsection
