@props(['label', 'value', 'icon' => 'chart', 'tone' => 'primary'])

@php
    $tones = [
        'primary' => 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-200',
        'success' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        'danger'  => 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300',
        'info'    => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300',
        'neutral' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'ec-card p-4']) }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $value }}</p>
        </div>
        <span class="flex h-10 w-10 items-center justify-center rounded-lg {{ $tones[$tone] }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
</div>
