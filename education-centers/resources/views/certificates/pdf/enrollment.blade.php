@extends('pdf.layout')

@section('title', $certificate->title)

@section('styles')
    .frame { border: 2px solid #0DAD98; padding: 24px 28px; min-height: 240mm; }
    .lead { text-align: center; font-size: 13px; margin: 14px 0 6px; }
    .student { text-align: center; font-size: 22px; font-weight: bold; margin: 10px 0 22px; }
    .body-text { text-align: center; margin: 8px 0 20px; }
@endsection

@section('content')
    <div class="frame">
        <h1 class="doc-title">{{ $certificate->title }}</h1>
        <div class="doc-subtitle">{{ __('Attestation administrative') }}</div>

        <p class="lead">{{ __('Le présent document atteste que') }}</p>
        <div class="student">{{ $certificate->student?->full_name }}</div>

        <p class="body-text">
            {{ __('est régulièrement inscrit(e) auprès de notre établissement au titre de la formation') }}
            <strong>{{ $certificate->formation?->translated('name') ?? '—' }}</strong>.
        </p>

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
    </div>
@endsection
