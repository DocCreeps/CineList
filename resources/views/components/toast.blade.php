{{--
    Pile de notifications ("toasts"), pilotée par le store Alpine "toast" (resources/js/app.js).
    A inclure une seule fois dans le layout principal — voir aussi <x-toast-bridge> pour relayer
    session('notice') après une redirection plein-page.
--}}
<div
    x-data
    class="pointer-events-none fixed inset-x-0 bottom-0 z-[200] flex flex-col items-center gap-2.5 p-4 sm:items-end sm:p-6"
    aria-live="polite"
    aria-atomic="true"
>
    <template x-for="item in $store.toast.items" :key="item.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-4"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave-end="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-4"
            x-on:mouseenter="$store.toast.pause(item.id)"
            x-on:mouseleave="$store.toast.resume(item.id)"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-2xl border p-3.5 shadow-2xl backdrop-blur-sm"
            :class="{
                'border-emerald-900/60 bg-emerald-950/90 text-emerald-300': item.type === 'success',
                'border-red-900/60 bg-red-950/90 text-red-300': item.type === 'error',
                'border-zinc-700/80 bg-zinc-900/95 text-zinc-200': item.type === 'info',
            }"
            role="status"
        >
            <span
                class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-bold"
                :class="{
                    'bg-emerald-800/50 text-emerald-300': item.type === 'success',
                    'bg-red-800/50 text-red-300': item.type === 'error',
                    'bg-zinc-700/60 text-zinc-300': item.type === 'info',
                }"
            >
                <span x-show="item.type === 'success'">✓</span>
                <span x-show="item.type === 'error'">✕</span>
                <span x-show="item.type === 'info'">i</span>
            </span>

            <p class="mt-0.5 flex-1 text-sm font-medium" x-text="item.message"></p>

            <button
                type="button"
                x-on:click="$store.toast.dismiss(item.id)"
                class="shrink-0 rounded-lg p-1 text-current/60 transition hover:bg-white/10 hover:text-current"
                aria-label="Fermer"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
