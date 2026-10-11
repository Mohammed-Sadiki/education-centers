@props(['name' => 'U', 'size' => 'md'])

@php
    $sizes = ['sm' => 'h-8 w-8 text-xs', 'md' => 'h-10 w-10 text-sm', 'lg' => 'h-14 w-14 text-lg'];
    $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-full bg-brand-100 font-semibold text-brand-700 dark:bg-brand-500/20 dark:text-brand-200 '.$sizes[$size]]) }}>
    {{ $initials ?: 'U' }}
</span>
