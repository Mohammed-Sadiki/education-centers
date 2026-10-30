@extends('pdf.layout')

@section('title', __('Facture').' '.$invoice->invoice_number)

@section('styles')
    .status-paid { color: #047857; }
    .status-unpaid { color: #b45309; }
@endsection

@section('content')
    @php
        $formationName = $invoice->enrollment?->formation?->translated('name')
            ?? $invoice->formation?->translated('name')
            ?? '—';
        $paid = (float) $invoice->total_paid;
        $remaining = (float) $invoice->remaining_amount;

        // Réduction accordée à l'inscription (si un prix final personnalisé a été
        // appliqué) : affichée explicitement sur le document financier (§8).
        $enrollment = $invoice->enrollment;
        $hasDiscount = $enrollment && $enrollment->hasDiscount();
        $officialPrice = $hasDiscount ? $enrollment->officialPriceReference() : 0.0;
        $finalPrice = $hasDiscount ? $enrollment->getPayableAmount() : 0.0;
        $discountPrice = $hasDiscount ? $enrollment->getDiscountAmount() : 0.0;
    @endphp

    <h1 class="doc-title">{{ __('Facture') }}</h1>
    <div class="doc-subtitle">{{ $invoice->invoice_number }}</div>

    <table class="meta">
        <tr>
            <td class="label">{{ __('Émetteur') }}</td>
            <td class="value">{{ $invoice->center?->translated('name') ?? __('Education Centers') }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Client / Étudiant') }}</td>
            <td class="value">{{ $invoice->student?->full_name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Formation') }}</td>
            <td class="value">{{ $formationName }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Date d\'émission') }}</td>
            <td class="value">{{ format_date($invoice->invoice_date, 'd M Y') }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Échéance') }}</td>
            <td class="value">{{ $invoice->due_date ? format_date($invoice->due_date, 'd M Y') : '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Statut') }}</td>
            <td class="value">{{ $invoice->status_label }}</td>
        </tr>
    </table>

    <h2>{{ __('Prestations facturées') }}</h2>
    <table class="grid">
        <thead>
            <tr>
                <th>{{ __('Désignation') }}</th>
                <th class="amount">{{ __('Qté') }}</th>
                <th class="amount">{{ __('Prix unitaire') }}</th>
                <th class="amount">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ __('Frais de formation') }} — {{ $formationName }}</td>
                <td class="amount">1</td>
                <td class="amount">{{ format_price((float) $invoice->total_amount, $invoice->currency) }}</td>
                <td class="amount">{{ format_price((float) $invoice->total_amount, $invoice->currency) }}</td>
            </tr>
        </tbody>
    </table>

    @if($hasDiscount)
        <h2>{{ __('Détail de la réduction accordée') }}</h2>
        <table class="totals">
            <tr>
                <td>{{ __('Tarif officiel') }}</td>
                <td class="amount">{{ format_price($officialPrice, $invoice->currency) }}</td>
            </tr>
            <tr>
                <td>{{ __('Réduction accordée') }}</td>
                <td class="amount status-unpaid">− {{ format_price($discountPrice, $invoice->currency) }}</td>
            </tr>
            <tr>
                <td>{{ __('Prix final de la formation') }}</td>
                <td class="amount">{{ format_price($finalPrice, $invoice->currency) }}</td>
            </tr>
        </table>
    @endif

    <table class="totals">
        <tr>
            <td>{{ __('Total') }}</td>
            <td class="amount">{{ format_price((float) $invoice->total_amount, $invoice->currency) }}</td>
        </tr>
        <tr>
            <td>{{ __('Payé') }}</td>
            <td class="amount status-paid">{{ format_price($paid, $invoice->currency) }}</td>
        </tr>
        <tr>
            <td>{{ __('Reste à payer') }}</td>
            <td class="amount status-unpaid">{{ format_price($remaining, $invoice->currency) }}</td>
        </tr>
        <tr class="grand">
            <td>{{ __('Solde') }}</td>
            <td class="amount">{{ $remaining > 0 ? __('Reste') : __('Soldé') }}</td>
        </tr>
    </table>

    <h2>{{ __('Historique des versements') }}</h2>
    <table class="grid">
        <thead>
            <tr>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Référence') }}</th>
                <th>{{ __('Mode') }}</th>
                <th class="amount">{{ __('Montant') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->payments->where('status', 'paid') as $payment)
                <tr>
                    <td>{{ format_date($payment->payment_date, 'd M Y') }}</td>
                    <td>{{ $payment->reference ?: '—' }}</td>
                    <td>{{ $payment->method_label }}</td>
                    <td class="amount">{{ format_price((float) $payment->amount, $payment->currency ?: $invoice->currency) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center;color:#64748b;">{{ __('Aucun versement enregistré') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($invoice->notes)
        <h2>{{ __('Notes') }}</h2>
        <p>{{ $invoice->notes }}</p>
    @endif

    <table class="signatures">
        <tr>
            <td><div class="signature-line">{{ __('Signature du client') }}</div></td>
            <td><div class="signature-line">{{ __('Cachet de l\'établissement') }}</div></td>
        </tr>
    </table>
@endsection
