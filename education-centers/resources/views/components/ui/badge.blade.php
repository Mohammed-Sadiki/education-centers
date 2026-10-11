@props(['tone' => 'neutral'])

@php
    $tones = [
        'neutral' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        'primary' => 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-200',
        'success' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        'warning' => 'bg-amber-50 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
        'danger'  => 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300',
        'info'    => 'bg-cyan-50 text-cyan-800 dark:bg-cyan-500/15 dark:text-cyan-300',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'ec-badge '.$tones[$tone]]) }}>
    {{ $slot }}
</span>
