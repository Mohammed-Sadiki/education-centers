@extends('layouts.app')
@section('title', __('Modifier la facture'))

@section('content')
<x-ui.page-header :title="__('Modifier la facture')" :description="$invoice->invoice_number" />
<form method="POST" action="{{ route('invoices.update', $invoice) }}" class="ec-card max-w-lg space-y-4 p-5">
    @csrf
    @method('PUT')
    <select name="status" class="ec-select" required>
        @foreach(['draft'=>'Brouillon','sent'=>'Envoyée','paid'=>'Payée','cancelled'=>'Annulée','overdue'=>'En retard'] as $k=>$v)
            <option value="{{ $k }}" @selected($invoice->status===$k)>{{ __($v) }}</option>
        @endforeach
    </select>
    <input type="date" name="due_date" class="ec-input" value="{{ optional($invoice->due_date)->format('Y-m-d') }}">
    <textarea name="notes" class="ec-textarea" rows="3">{{ $invoice->notes }}</textarea>
    <button class="ec-btn-primary">{{ __('Enregistrer') }}</button>
</form>
@endsection
