{{-- Modale de confirmation custom, pilotée par le store Alpine "confirmModal".
     A inclure une seule fois dans le layout principal.
     Usage depuis n'importe quel composant Livewire :

     <button
         type="button"
         x-on:click="$store.confirmModal.open(@js('Supprimer ?'), () => $wire.remove({{ $item->id }}))"
     >Supprimer</button>
--}}
<div
    x-data
    x-show="$store.confirmModal.show"
    x-cloak
    x-on:keydown.escape.window="$store.confirmModal.close()"
    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    :aria-hidden="!$store.confirmModal.show"
>
    {{-- Fond --}}
    <div
        class="absolute inset-0 bg-zinc-950/80 backdrop-blur-sm"
        x-on:click="$store.confirmModal.close()"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    {{-- Panneau --}}
    <div
        class="relative w-full max-w-sm rounded-2xl border border-zinc-800 bg-zinc-900 p-6 shadow-2xl"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <h3 class="text-sm font-bold text-zinc-100" x-text="$store.confirmModal.title"></h3>
        <p class="mt-2 text-sm text-zinc-400" x-text="$store.confirmModal.message"></p>

        <div class="mt-6 flex justify-end gap-3">
            <button
                type="button"
                x-on:click="$store.confirmModal.close()"
                class="rounded-xl border border-zinc-700 px-4 py-2 text-sm font-semibold text-zinc-300 transition hover:bg-zinc-800/60"
                x-text="$store.confirmModal.cancelLabel"
            ></button>
            <button
                type="button"
                x-on:click="$store.confirmModal.confirm()"
                :class="$store.confirmModal.danger
                    ? 'bg-red-600 hover:bg-red-500 text-white'
                    : 'bg-amber-500 hover:bg-amber-400 text-zinc-950'"
                class="rounded-xl px-4 py-2 text-sm font-bold transition"
                x-text="$store.confirmModal.confirmLabel"
            ></button>
        </div>
    </div>
</div>
