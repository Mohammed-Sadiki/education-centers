@extends('layouts.app')
@section('title', 'Paiements')

@section('content')
<x-ui.page-header title="Paiements" description="Suivi financier des élèves.">
    @can('create', \App\Models\Payment::class)
        <a href="{{ route('payments.create') }}" class="ec-btn-primary"><x-icon name="plus" class="h-4 w-4" /> Enregistrer</a>
    @endcan
</x-ui.page-header>
<div class="mb-6 grid gap-4 sm:grid-cols-2">
    <x-ui.stat label="Total payé (filtre actuel)" :value="number_format((float) $totalPaid, 2, ',', ' ').' €'" icon="banknotes" tone="success" />
    <x-ui.stat label="Paiements" :value="$payments->total()" icon="document" />
</div>
<x-ui.card class="mb-4">
    <form method="GET" class="grid gap-3 md:grid-cols-4">
        <select name="student_id" class="ec-select">
            <option value="">Tous les élèves</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" @selected(request('student_id')==$student->id)>{{ $student->full_name }}</option>
            @endforeach
        </select>
        <select name="status" class="ec-select">
            <option value="">Tous les statuts</option>
            @foreach(['pending'=>'En attente','paid'=>'Payé','cancelled'=>'Annulé','refunded'=>'Remboursé'] as $k=>$v)
                <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <select name="method" class="ec-select">
            <option value="">Toutes les méthodes</option>
            @foreach(['cash'=>'Espèces','card'=>'Carte','bank_transfer'=>'Virement','other'=>'Autre'] as $k=>$v)
                <option value="{{ $k }}" @selected(request('method')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <button class="ec-btn-secondary">Filtrer</button>
    </form>
</x-ui.card>
<div class="ec-table-wrap">
    <table class="ec-table">
        <thead><tr><th>Référence</th><th>Étudiant</th><th>Montant</th><th>Date</th><th>Méthode</th><th>Statut</th><th></th></tr></thead>
        <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td class="font-mono text-xs">{{ $payment->reference }}</td>
                    <td>{{ $payment->student?->full_name }}</td>
                    <td>{{ number_format((float) $payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</td>
                    <td>{{ optional($payment->payment_date)->format('d/m/Y') }}</td>
                    <td>{{ $payment->method_label }}</td>
                    <td><x-status-badge type="payment" :status="$payment->status" /></td>
                    <td class="text-right"><a href="{{ route('payments.show', $payment) }}" class="ec-btn-ghost ec-btn-sm">Voir</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-ui.empty title="Aucun paiement" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($payments->hasPages())<div class="mt-4">{{ $payments->links() }}</div>@endif
@endsection
