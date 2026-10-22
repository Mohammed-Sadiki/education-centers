@props([
    'action',
    'method' => 'DELETE',
    'title' => __('Confirmer'),
    'message' => __('Cette action est irréversible.'),
    'confirm' => __('Confirmer'),
])

<div x-data="{ open: false }" class="inline-flex">
    <button type="button" {{ $attributes }} x-on:click="open = true">{{ $slot }}</button>
    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/50 p-4"
            role="dialog"
            aria-modal="true"
            x-on:keydown.escape.window="open = false"
        >
            <div class="absolute inset-0" x-on:click="open = false"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white p-5 shadow-xl dark:bg-slate-900" x-trap="open">
                <h2 class="text-lg font-semibold">{{ $title }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $message }}</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="ec-btn-secondary" x-on:click="open = false">{{ __('Annuler') }}</button>
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        @method($method)
                        {{ $form ?? '' }}
                        <button type="submit" class="ec-btn-danger">{{ $confirm }}</button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
