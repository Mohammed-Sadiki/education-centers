@props(['name', 'title' => '', 'max' => 'lg'])

@php
    $widths = ['sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg', 'xl' => 'max-w-xl', '2xl' => 'max-w-2xl'];
@endphp

<div
    x-data="{ show: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    :aria-labelledby="'modal-title-{{ $name }}'"
>
    <div class="absolute inset-0 bg-slate-900/50" x-on:click="show = false"></div>
    <div
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="relative w-full {{ $widths[$max] }} rounded-xl bg-white p-5 shadow-xl dark:bg-slate-900"
        x-trap.noscroll="show"
    >
        <div class="mb-4 flex items-start justify-between gap-3">
            <h2 id="modal-title-{{ $name }}" class="text-lg font-semibold text-slate-900 dark:text-white">{{ $title }}</h2>
            <button type="button" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" x-on:click="show = false" aria-label="Fermer">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>
        {{ $slot }}
    </div>
</div>
