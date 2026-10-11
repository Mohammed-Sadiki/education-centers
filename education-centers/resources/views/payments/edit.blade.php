@extends('layouts.app')
@section('title', 'Modifier le paiement')

@section('content')
<x-ui.page-header title="Modifier le paiement" :description="$payment->reference" />
<form method="POST" action="{{ route('payments.update', $payment) }}" class="ec-card max-w-lg space-y-4 p-5">
    @csrf
    @method('PUT')
    <div>
        <label class="ec-label">Statut</label>
        <select name="status" class="ec-select" required>
            @foreach(['pending'=>'En attente','paid'=>'Payé','cancelled'=>'Annulé','refunded'=>'Remboursé'] as $k=>$v)
                <option value="{{ $k }}" @selected(old('status', $payment->status)===$k)>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="ec-label">Notes</label>
        <textarea name="notes" class="ec-textarea" rows="3">{{ old('notes', $payment->notes) }}</textarea>
    </div>
    <button class="ec-btn-primary">Enregistrer</button>
</form>
@endsection
