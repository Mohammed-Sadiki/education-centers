<div
    x-data
    x-init="
        @if(session('success')) $store.ui.showToast('success', @js(session('success'))); @endif
        @if(session('error')) $store.ui.showToast('error', @js(session('error'))); @endif
        @if(session('warning')) $store.ui.showToast('warning', @js(session('warning'))); @endif
        @if(session('info')) $store.ui.showToast('info', @js(session('info'))); @endif
    "
    class="pointer-events-none fixed end-4 top-4 z-[70] w-full max-w-sm"
>
    <div
        x-show="$store.ui.toast"
        x-cloak
        class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white p-4 shadow-lg dark:border-slate-700 dark:bg-slate-900"
        :class="{
            'border-emerald-200': $store.ui.toast?.type === 'success',
            'border-red-200': $store.ui.toast?.type === 'error',
            'border-amber-200': $store.ui.toast?.type === 'warning',
            'border-cyan-200': $store.ui.toast?.type === 'info'
        }"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
        role="status"
    >
        <p class="flex-1 text-sm text-slate-800 dark:text-slate-100" x-text="$store.ui.toast?.message"></p>
        <button type="button" class="text-slate-400 hover:text-slate-700" x-on:click="$store.ui.hideToast()" aria-label="{{ __('Fermer la notification') }}">
            <x-icon name="x" class="h-4 w-4" />
        </button>
    </div>
</div>
