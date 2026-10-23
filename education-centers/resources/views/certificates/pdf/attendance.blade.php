@extends('pdf.layout')

@section('title', $certificate->title)

@section('styles')
    .lead { text-align: center; font-size: 13px; margin: 14px 0 6px; }
    .student { text-align: center; font-size: 20px; font-weight: bold; margin: 8px 0 18px; }
    .summary { width: 70%; margin: 0 auto 18px; }
@endsection

@section('content')
    @php
        $rows = $attendances ?? collect();
        $total = $rows->count();
        $present = $rows->where('status', 'present')->count();
        $absent = $rows->where('status', 'absent')->count();
        $late = $rows->where('status', 'late')->count();
        $rate = $total > 0 ? round(($present / $total) * 100) : null;
    @endphp

    <h1 class="doc-title">{{ $certificate->title }}</h1>
    <div class="doc-subtitle">{{ __('Attestation administrative') }}</div>

    <p class="lead">{{ __('Le présent document atteste que') }}</p>
    <div class="student">{{ $certificate->student?->full_name }}</div>
    <p style="text-align:center;">
        {{ __('a suivi la formation') }} <strong>{{ $certificate->formation?->translated('name') ?? '—' }}</strong>.
    </p>

    <h2>{{ __('Récapitulatif de présence') }}</h2>
    <table class="grid summary">
        <thead>
            <tr>
                <th>{{ __('Séances') }}</th>
                <th>{{ __('Présences') }}</th>
                <th>{{ __('Absences') }}</th>
                <th>{{ __('Retards') }}</th>
                <th class="amount">{{ __('Taux') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="amount">{{ $total }}</td>
                <td class="amount">{{ $present }}</td>
                <td class="amount">{{ $absent }}</td>
                <td class="amount">{{ $late }}</td>
                <td class="amount">{{ $rate !== null ? $rate.' %' : '—' }}</td>
            </tr>
        </tbody>
    </table>

    <table class="meta">
        <tr>
            <td class="label">{{ __('Numéro du document') }}</td>
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
            <td><div class="signature-line">{{ __('Signature et cachet de l\'établissement') }}</div></td>
            <td><div class="signature-line">{{ __('Le Directeur') }}</div></td>
        </tr>
    </table>
@endsection
