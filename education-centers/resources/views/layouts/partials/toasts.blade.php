<div
    x-data
    x-init="
        @if(session('success')) $store.ui.showToast('success', @js(session('success'))); @endif
        @if(session('error')) $store.ui.showToast('error', @js(session('error'))); @endif
        @if(session('warning')) $store.ui.showToast('warning', @js(session('warning'))); @endif
        @if(session('info')) $store.ui.showToast('info', @js(session('info'))); @endif
    "
    class="pointer-events-none fixed right-4 top-4 z-[70] w-full max-w-sm"
>
    <template x-if="$store.ui.toast">
        <div
            class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white p-4 shadow-lg dark:border-slate-700 dark:bg-slate-900"
            :class="{
                'border-emerald-200': $store.ui.toast.type === 'success',
                'border-red-200': $store.ui.toast.type === 'error',
                'border-amber-200': $store.ui.toast.type === 'warning',
                'border-cyan-200': $store.ui.toast.type === 'info'
            }"
            x-transition
            role="status"
        >
            <p class="flex-1 text-sm text-slate-800 dark:text-slate-100" x-text="$store.ui.toast.message"></p>
            <button type="button" class="text-slate-400 hover:text-slate-700" x-on:click="$store.ui.hideToast()" aria-label="{{ __('Fermer la notification') }}">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    </template>
</div>
