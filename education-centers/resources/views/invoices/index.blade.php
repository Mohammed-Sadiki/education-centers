@extends('layouts.app')
@section('title', 'Factures')

@section('content')
<x-ui.page-header title="Factures" description="Facturation des formations.">
    @can('create', \App\Models\Invoice::class)
        <a href="{{ route('invoices.create') }}" class="ec-btn-primary"><x-icon name="plus" class="h-4 w-4" /> Nouvelle facture</a>
    @endcan
</x-ui.page-header>
<x-ui.card class="mb-4">
    <form method="GET" class="flex max-w-sm gap-2">
        <select name="status" class="ec-select">
            <option value="">Tous les statuts</option>
            @foreach(['draft'=>'Brouillon','sent'=>'Envoyée','paid'=>'Payée','cancelled'=>'Annulée','overdue'=>'En retard'] as $k=>$v)
                <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <button class="ec-btn-secondary">Filtrer</button>
    </form>
</x-ui.card>
<div class="ec-table-wrap">
    <table class="ec-table">
        <thead><tr><th>Numéro</th><th>Étudiant</th><th>Date</th><th>Total</th><th>Payé</th><th>Restant</th><th>Statut</th><th></th></tr></thead>
        <tbody>
            @forelse($invoices as $invoice)
                @php
                    $paid = (float) $invoice->payments->where('status','paid')->sum('amount');
                    $remaining = max(0, (float) $invoice->total_amount - $paid);
                @endphp
                <tr>
                    <td class="font-mono text-xs">{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->student?->full_name }}</td>
                    <td>{{ optional($invoice->invoice_date)->format('d/m/Y') }}</td>
                    <td>{{ number_format((float) $invoice->total_amount, 2, ',', ' ') }} {{ $invoice->currency }}</td>
                    <td>{{ number_format($paid, 2, ',', ' ') }}</td>
                    <td>{{ number_format($remaining, 2, ',', ' ') }}</td>
                    <td><x-status-badge type="invoice" :status="$invoice->status" /></td>
                    <td class="text-right"><a href="{{ route('invoices.show', $invoice) }}" class="ec-btn-ghost ec-btn-sm">Voir</a></td>
                </tr>
            @empty
                <tr><td colspan="8"><x-ui.empty title="Aucune facture" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($invoices->hasPages())<div class="mt-4">{{ $invoices->links() }}</div>@endif
@endsection
