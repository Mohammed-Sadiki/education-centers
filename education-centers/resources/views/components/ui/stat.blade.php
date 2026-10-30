@props(['label', 'value', 'icon' => 'chart', 'tone' => 'primary', 'delta' => null, 'hint' => null, 'href' => null])

@php
    $chips = [
        'primary' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300',
        'success' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300',
        'warning' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
        'danger'  => 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300',
        'info'    => 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300',
        'neutral' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    ];
    $chip = $chips[$tone] ?? $chips['primary'];
    $deltaUp = is_string($delta) ? !str_starts_with(trim($delta), '-') : (is_numeric($delta) ? $delta >= 0 : true);

    // Carte cliquable : rendue en <a> accessible quand un href est fourni
    // (sinon <div> comme avant). Design strictement identique via .ec-kpi ;
    // au survol, la carte se soulève déjà (translateY + ombre en CSS).
    $cardAttrs = $attributes->merge([
        'class' => $href
            ? 'ec-kpi cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40'
            : 'ec-kpi',
    ]);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $cardAttrs }}>
@else
    <div {{ $cardAttrs }}>
@endif
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="ec-kpi__label truncate">{{ $label }}</p>
            <p class="ec-kpi__value mt-2">{{ $value }}</p>
        </div>
        <span class="ec-kpi__chip {{ $chip }}">
            <x-icon :name="$icon" class="h-6 w-6" />
        </span>
    </div>
    @if($delta !== null || $hint !== null)
        <div class="mt-3 flex items-center gap-1.5 text-xs">
            @if($delta !== null)
                <span class="ec-kpi__delta {{ $deltaUp ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    <x-icon :name="$deltaUp ? 'arrow-trending-up' : 'arrow-trending-down'" class="h-3.5 w-3.5" />
                    {{ $delta }}
                </span>
            @endif
            @if($hint !== null)
                <span class="text-slate-400 dark:text-slate-500">{{ $hint }}</span>
            @endif
        </div>
    @endif
@if($href)
    </a>
@else
    </div>
@endif
