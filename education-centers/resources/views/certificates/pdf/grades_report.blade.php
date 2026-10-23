@extends('pdf.layout')

@section('title', $certificate->title)

@section('styles')
    .who { text-align: center; margin-bottom: 16px; }
    .who .name { font-size: 18px; font-weight: bold; }
    .average { margin-top: 16px; width: 55%; margin-{{ is_rtl() ? 'left' : 'right' }}: auto; }
    .avg-grand { font-size: 15px; font-weight: bold; color: #0DAD98; }
@endsection

@section('content')
    @php
        $rows = $grades ?? collect();
        $sumCoef = 0.0;
        $sumWeighted = 0.0;
        foreach ($rows as $g) {
            if ($g->is_absent || $g->grade === null) {
                continue;
            }
            $coef = (float) ($g->evaluation?->coefficient ?? 1);
            $coef = $coef > 0 ? $coef : 1;
            $sumCoef += $coef;
            $sumWeighted += (float) $g->grade * $coef;
        }
        $average = $sumCoef > 0 ? round($sumWeighted / $sumCoef, 2) : null;
    @endphp

    <h1 class="doc-title">{{ $certificate->title }}</h1>
    <div class="doc-subtitle">{{ __('Relevé officiel des notes') }}</div>

    <div class="who">
        <div>{{ __('Étudiant') }} : <span class="name">{{ $certificate->student?->full_name }}</span></div>
        <div class="muted">{{ __('Formation') }} : {{ $certificate->formation?->translated('name') ?? '—' }}</div>
    </div>

    <table class="grid">
        <thead>
            <tr>
                <th>{{ __('Évaluation') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Date') }}</th>
                <th class="amount">{{ __('Note') }}</th>
                <th class="amount">{{ __('Barème') }}</th>
                <th class="amount">{{ __('Coef.') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $grade)
                <tr>
                    <td>{{ $grade->evaluation?->title ?? '—' }}</td>
                    <td>{{ $grade->evaluation?->type_label ?? '—' }}</td>
                    <td>{{ $grade->evaluation?->evaluation_date ? format_date($grade->evaluation->evaluation_date, 'd M Y') : ($grade->graded_at ? format_date($grade->graded_at, 'd M Y') : '—') }}</td>
                    <td class="amount">{{ $grade->is_absent ? __('Absent') : ($grade->grade !== null ? number_format((float) $grade->grade, 2, ',', ' ') : '—') }}</td>
                    <td class="amount">{{ number_format((float) ($grade->evaluation?->max_grade ?? 20), 2, ',', ' ') }}</td>
                    <td class="amount">{{ number_format((float) ($grade->evaluation?->coefficient ?? 1), 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;color:#64748b;">{{ __('Aucune note enregistrée') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals average">
        <tr class="grand">
            <td>{{ __('Moyenne générale') }}</td>
            <td class="amount avg-grand">{{ $average !== null ? number_format($average, 2, ',', ' ').' / 20' : '—' }}</td>
        </tr>
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
