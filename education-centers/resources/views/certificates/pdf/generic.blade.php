<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ current_dir() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: {{ is_rtl() ? "'xbriyaz', 'dejavusans', sans-serif" : "'dejavusans', sans-serif" }};
            color: #0f172a;
            font-size: 12px;
            line-height: 1.6;
            margin: 0;
        }
        .frame {
            border: 2px solid #0DAD98;
            padding: 26px 30px;
            min-height: 250mm;
        }
        .header { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .header td { vertical-align: top; }
        .center-name { font-size: 20px; font-weight: bold; color: #0DAD98; }
        .muted { color: #64748b; font-size: 10px; }
        .document-number { text-align: {{ is_rtl() ? 'left' : 'right' }}; font-size: 10px; color: #64748b; }
        h1 {
            text-align: center;
            font-size: 26px;
            margin: 30px 0 6px;
            color: #0f172a;
            letter-spacing: 1px;
        }
        .subtitle { text-align: center; color: #0DAD98; font-size: 12px; margin-bottom: 28px; }
        .lead { text-align: center; font-size: 13px; margin: 0 0 8px; }
        .student { text-align: center; font-size: 22px; font-weight: bold; margin: 10px 0 26px; }
        .details { width: 100%; border-collapse: collapse; margin: 20px 0 28px; }
        .details td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
        .details .label { color: #64748b; width: 42%; }
        .details .value { font-weight: bold; }
        .note { margin-top: 24px; font-size: 10px; color: #64748b; text-align: center; }
        .signatures { width: 100%; border-collapse: collapse; margin-top: 42px; }
        .signatures td { width: 50%; text-align: center; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #94a3b8; margin: 0 30px; padding-top: 6px; font-size: 10px; color: #475569; }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            background: #ecfdf5;
            color: #047857;
            font-size: 9px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="frame">
    <table class="header">
        <tr>
            <td>
                <div class="center-name">{{ $certificate->center?->translated('name') ?? __('Education Centers') }}</div>
                <div class="muted">
                    {{ $certificate->center?->translated('address') }}
                    @if($certificate->center?->city) — {{ $certificate->center->city }} @endif
                </div>
                <div class="muted">
                    @if($certificate->center?->phone) {{ $certificate->center->phone }} @endif
                    @if($certificate->center?->email) · {{ $certificate->center->email }} @endif
                </div>
            </td>
            <td class="document-number">
                {{ __('N°') }} {{ $certificate->certificate_number }}<br>
                {{ format_date($certificate->issued_date, 'd M Y') }}
            </td>
        </tr>
    </table>

    <h1>{{ $certificate->title }}</h1>
    <div class="subtitle">{{ __('Certificat officiel') }} · {{ __('Education Centers') }}</div>

    <p class="lead">{{ __('Le présent document atteste que') }}</p>
    <div class="student">{{ $certificate->student?->full_name }}</div>

    <table class="details">
        <tr>
            <td class="label">{{ __('Formation') }}</td>
            <td class="value">{{ $certificate->formation?->translated('name') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Numéro de certificat') }}</td>
            <td class="value">{{ $certificate->certificate_number }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Date d’émission') }}</td>
            <td class="value">{{ format_date($certificate->issued_date, 'd M Y') }}</td>
        </tr>
        @if($certificate->valid_until)
            <tr>
                <td class="label">{{ __('Valable jusqu’au') }}</td>
                <td class="value">{{ format_date($certificate->valid_until, 'd M Y') }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">{{ __('Code de vérification') }}</td>
            <td class="value">{{ $certificate->verify_token }}</td>
        </tr>
    </table>

    <p style="text-align:center;">
        <span class="badge">{{ __('Document vérifiable en ligne') }}</span>
    </p>
    <p class="note">
        {{ __('Vérifiez l’authenticité de ce document sur la plateforme avec le code ci-dessus.') }}
    </p>

    <table class="signatures">
        <tr>
            <td>
                <div class="signature-line">{{ __('Signature et cachet de l’établissement') }}</div>
            </td>
            <td>
                <div class="signature-line">{{ __('Le Directeur') }}</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
