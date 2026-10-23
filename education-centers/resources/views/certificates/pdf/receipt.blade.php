@extends('pdf.layout')

@section('title', $certificate->title)

@section('styles')
    .who { text-align: center; margin-bottom: 16px; }
    .who .name { font-size: 18px; font-weight: bold; }
@endsection

@section('content')
    @php
        $rows = ($payments ?? collect())->where('status', 'paid');
        $totalReceived = (float) $rows->sum('amount');
        $currency = $rows->first()?->currency ?? 'MAD';
    @endphp

    <h1 class="doc-title">{{ $certificate->title }}</h1>
    <div class="doc-subtitle">{{ __('Attestation de paiement') }}</div>

    <div class="who">
        <div>{{ __('Payeur') }} : <span class="name">{{ $certificate->student?->full_name }}</span></div>
        <div class="muted">{{ __('Centre') }} : {{ $certificate->center?->translated('name') ?? '—' }}</div>
    </div>

    <table class="grid">
        <thead>
            <tr>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Référence') }}</th>
                <th>{{ __('Mode de paiement') }}</th>
                <th class="amount">{{ __('Montant encaissé') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $payment)
                <tr>
                    <td>{{ format_date($payment->payment_date, 'd M Y') }}</td>
                    <td>{{ $payment->reference ?: '—' }}</td>
                    <td>{{ $payment->method_label }}</td>
                    <td class="amount">{{ format_price((float) $payment->amount, $payment->currency ?: $currency) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center;color:#64748b;">{{ __('Aucun paiement enregistré') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr class="grand">
            <td>{{ __('Total encaissé') }}</td>
            <td class="amount">{{ format_price($totalReceived, $currency) }}</td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td class="label">{{ __('Numéro du reçu') }}</td>
            <td class="value">{{ $certificate->certificate_number }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Date d\'émission') }}</td>
            <td class="value">{{ format_date($certificate->issued_date, 'd M Y') }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Code de vérification') }}</td>
            <td class="value">{{ $certificate->verify_token }}</td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td><div class="signature-line">{{ __('Signature du caissier') }}</div></td>
            <td><div class="signature-line">{{ __('Cachet de l\'établissement') }}</div></td>
        </tr>
    </table>
@endsection
