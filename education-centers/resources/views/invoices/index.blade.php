@extends('layouts.app')
@section('title', __('Factures'))

@section('content')
<x-ui.page-header :title="__('Factures')" :description="__('Facturation des formations.')">
    @can('create', \App\Models\Invoice::class)
        <a href="{{ route('invoices.create') }}" class="ec-btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('Nouvelle facture') }}</a>
    @endcan
</x-ui.page-header>
<x-ui.card class="mb-4">
    <form method="GET" class="flex max-w-sm gap-2">
        <select name="status" class="ec-select">
            <option value="">{{ __('Tous les statuts') }}</option>
            @foreach(['draft'=>'Brouillon','sent'=>'Envoyée','paid'=>'Payée','cancelled'=>'Annulée','overdue'=>'En retard'] as $k=>$v)
                <option value="{{ $k }}" @selected(request('status')===$k)>{{ __($v) }}</option>
            @endforeach
        </select>
        <button class="ec-btn-secondary">{{ __('Filtrer') }}</button>
    </form>
</x-ui.card>
<div class="ec-table-wrap">
    <table class="ec-table">
        <thead><tr><th>{{ __('Numéro') }}</th><th>{{ __('Étudiant') }}</th><th>{{ __('Date') }}</th><th>{{ __('Total') }}</th><th>{{ __('Payé') }}</th><th>{{ __('Restant') }}</th><th>{{ __('Statut') }}</th><th></th></tr></thead>
        <tbody>
            @forelse($invoices as $invoice)
                @php
                    $paid = (float) $invoice->payments->where('status','paid')->sum('amount');
                    $remaining = max(0, (float) $invoice->total_amount - $paid);
                @endphp
                <tr>
                    <td class="font-mono text-xs">{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->student?->full_name }}</td>
                    <td>{{ format_date($invoice->invoice_date, 'd/m/Y') }}</td>
                    <td>{{ format_number((float) $invoice->total_amount, 2) }} {{ $invoice->currency }}</td>
                    <td>{{ format_number($paid, 2) }}</td>
                    <td>{{ format_number($remaining, 2) }}</td>
                    <td><x-status-badge type="invoice" :status="$invoice->status" /></td>
                    <td class="text-right"><a href="{{ route('invoices.show', $invoice) }}" class="ec-btn-ghost ec-btn-sm">{{ __('Voir') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="8"><x-ui.empty :title="__('Aucune facture')" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($invoices->hasPages())<div class="mt-4">{{ $invoices->links() }}</div>@endif
@endsection
