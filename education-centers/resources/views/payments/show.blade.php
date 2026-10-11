@extends('layouts.app')
@section('title', $payment->reference)

@section('content')
<x-ui.page-header title="Paiement {{ $payment->reference }}" :description="$payment->student?->full_name">
    <a href="{{ route('payments.index') }}" class="ec-btn-secondary">Retour</a>
    @can('update', $payment)
        <a href="{{ route('payments.edit', $payment) }}" class="ec-btn-primary">Modifier</a>
    @endcan
</x-ui.page-header>
<x-ui.card>
    <dl class="grid gap-3 text-sm sm:grid-cols-2">
        <div><dt class="text-slate-500">Montant</dt><dd class="text-xl font-semibold">{{ number_format((float) $payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</dd></div>
        <div><dt class="text-slate-500">Statut</dt><dd><x-status-badge type="payment" :status="$payment->status" /></dd></div>
        <div><dt class="text-slate-500">Date</dt><dd>{{ optional($payment->payment_date)->format('d/m/Y') }}</dd></div>
        <div><dt class="text-slate-500">Méthode</dt><dd>{{ $payment->method_label }}</dd></div>
        <div><dt class="text-slate-500">Reçu</dt><dd class="font-mono text-xs">{{ $payment->reference }}</dd></div>
        <div><dt class="text-slate-500">Enregistré par</dt><dd>{{ $payment->recorder?->name ?? '—' }}</dd></div>
    </dl>
</x-ui.card>
@endsection
