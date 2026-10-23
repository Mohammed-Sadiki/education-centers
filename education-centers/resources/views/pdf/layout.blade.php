<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ current_dir() }}">
<head>
    <meta charset="utf-8">
    <title>@yield('title', __('Education Centers'))</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: {{ is_rtl() ? "'xbriyaz', 'dejavusans', sans-serif" : "'dejavusans', sans-serif" }};
            color: #0f172a;
            font-size: 12px;
            line-height: 1.6;
            margin: 0;
        }
        .pdf-header { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .pdf-header td { vertical-align: top; }
        .center-name { font-size: 18px; font-weight: bold; color: #0DAD98; }
        .muted { color: #64748b; font-size: 10px; }
        .doc-ref { text-align: {{ is_rtl() ? 'left' : 'right' }}; font-size: 10px; color: #64748b; }
        h1.doc-title { text-align: center; font-size: 22px; margin: 18px 0 4px; letter-spacing: .5px; }
        .doc-subtitle { text-align: center; color: #0DAD98; font-size: 12px; margin-bottom: 22px; }
        h2 { font-size: 13px; margin: 22px 0 8px; color: #0f172a; }
        table.meta { width: 100%; border-collapse: collapse; margin: 10px 0 16px; }
        table.meta td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        table.meta .label { color: #64748b; width: 38%; }
        table.meta .value { font-weight: bold; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.grid th {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 7px 8px;
            font-size: 10px;
            text-align: {{ is_rtl() ? 'right' : 'left' }};
            color: #475569;
        }
        table.grid td { border: 1px solid #e2e8f0; padding: 6px 8px; }
        table.grid td.amount, table.grid th.amount { text-align: {{ is_rtl() ? 'left' : 'right' }}; }
        .totals { margin-top: 14px; width: 55%; margin-{{ is_rtl() ? 'left' : 'right' }}: auto; }
        .totals td { padding: 5px 8px; }
        .totals .grand { font-size: 15px; font-weight: bold; color: #0DAD98; border-top: 2px solid #0DAD98; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 10px; background: #ecfdf5; color: #047857; font-size: 10px; font-weight: bold; }
        .signatures { width: 100%; border-collapse: collapse; margin-top: 40px; }
        .signatures td { width: 50%; text-align: center; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #94a3b8; margin: 0 24px; padding-top: 6px; font-size: 10px; color: #475569; }
        .pdf-footer { margin-top: 30px; font-size: 9px; color: #64748b; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 8px; }
        @yield('styles')
    </style>
</head>
<body>
    @include('pdf.partials.center-header', ['center' => $center ?? null])

    @yield('content')

    <div class="pdf-footer">@yield('footer', __('Document généré électroniquement par Education Centers.'))</div>
</body>
</html>
