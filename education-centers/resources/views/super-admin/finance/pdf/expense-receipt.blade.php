<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ current_dir() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Reçu de dépense') }} — {{ $expense->reference }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: {{ is_rtl() ? "'xbriyaz', 'dejavusans', sans-serif" : "'dejavusans', sans-serif" }};
            color: #0f172a;
            font-size: 12px;
            line-height: 1.55;
            margin: 0;
        }
        .header { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .header td { vertical-align: top; }
        .center-name { font-size: 18px; font-weight: bold; color: #0DAD98; }
        .muted { color: #64748b; font-size: 10px; }
        .document-title { text-align: {{ is_rtl() ? 'left' : 'right' }}; }
        .document-title h1 { margin: 0; font-size: 20px; }
        h2 { font-size: 13px; margin: 22px 0 8px; color: #0f172a; }
        .meta { width: 100%; border-collapse: collapse; margin: 10px 0 18px; }
        .meta td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        .meta .label { color: #64748b; width: 34%; }
        .meta .value { font-weight: bold; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 7px 8px;
            font-size: 10px;
            text-align: {{ is_rtl() ? 'right' : 'left' }};
            color: #475569;
        }
        table.items td { border: 1px solid #e2e8f0; padding: 7px 8px; }
        table.items td.amount, table.items th.amount { text-align: {{ is_rtl() ? 'left' : 'right' }}; }
        .total {
            margin-top: 14px;
            text-align: {{ is_rtl() ? 'left' : 'right' }};
            font-size: 15px;
            font-weight: bold;
        }
        .total span { color: #0DAD98; }
        .footer { margin-top: 34px; font-size: 10px; color: #64748b; text-align: center; }
        .signature { margin-top: 40px; width: 45%; text-align: center; }
        .signature-line { border-top: 1px solid #94a3b8; padding-top: 6px; font-size: 10px; color: #475569; }
    </style>
</head>
<body>
@php
    $methodLabel = match ($expense->payment_method) {
        'cash' => __('Espèces'),
        'bank_transfer' => __('Virement bancaire'),
        'check' => __('Chèque'),
        'credit_card' => __('Carte bancaire'),
        default => $expense->payment_method ?? '—',
    };
@endphp

<table class="header">
    <tr>
        <td>
            <div class="center-name">{{ $expense->center?->translated('name') ?? __('Education Centers') }}</div>
            <div class="muted">
                {{ $expense->center?->translated('address') }}
                @if($expense->center?->city) — {{ $expense->center->city }} @endif
            </div>
            <div class="muted">
                @if($expense->center?->phone) {{ $expense->center->phone }} @endif
                @if($expense->center?->email) · {{ $expense->center->email }} @endif
            </div>
        </td>
        <td class="document-title">
            <h1>{{ __('Reçu de dépense') }}</h1>
            <div class="muted">{{ $expense->reference }}</div>
        </td>
    </tr>
</table>

<h2>{{ __('Informations de la dépense') }}</h2>
<table class="meta">
    <tr>
        <td class="label">{{ __('Titre') }}</td>
        <td class="value">{{ $expense->translated('title') }}</td>
    </tr>
    <tr>
        <td class="label">{{ __('Catégorie') }}</td>
        <td class="value">{{ $expense->category?->translated('name') ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">{{ __('Date') }}</td>
        <td class="value">{{ format_date($expense->expense_date, 'd M Y') }}</td>
    </tr>
    <tr>
        <td class="label">{{ __('Mode de paiement') }}</td>
        <td class="value">{{ $methodLabel }}</td>
    </tr>
    <tr>
        <td class="label">{{ __('Fournisseur') }}</td>
        <td class="value">{{ $expense->supplier ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">{{ __('Référence') }}</td>
        <td class="value">{{ $expense->reference ?: '—' }}</td>
    </tr>
</table>

@if($expense->description)
    <h2>{{ __('Description') }}</h2>
    <p>{{ $expense->translated('description') }}</p>
@endif

<h2>{{ __('Détail des articles') }}</h2>
<table class="items">
    <thead>
        <tr>
            <th>{{ __('Article') }}</th>
            <th class="amount">{{ __('Prix unitaire') }}</th>
            <th class="amount">{{ __('Quantité') }}</th>
            <th class="amount">{{ __('Total') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($expense->items as $item)
            <tr>
                <td>{{ $item->translated('name') }}</td>
                <td class="amount">{{ format_price($item->unit_price, $expense->currency) }}</td>
                <td class="amount">{{ $item->quantity }}</td>
                <td class="amount">{{ format_price($item->total_price, $expense->currency) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align:center;color:#64748b;">{{ __('Aucun article détaillé') }}</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="total">{{ __('Total') }} : <span>{{ format_price($expense->amount, $expense->currency) }}</span></div>

<div class="signature">
    <div class="signature-line">{{ __('Signature du responsable') }}</div>
</div>

<p class="footer">{{ __('Document généré électroniquement par Education Centers.') }}</p>
</body>
</html>
