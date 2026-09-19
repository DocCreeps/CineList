<div>
    <h1 class="font-display text-3xl tracking-wide text-zinc-100">VÉRIFICATION</h1>
    <p class="mt-1 text-sm text-zinc-500">
        @if ($usingRecoveryCode)
            Entrez l'un de vos codes de récupération.
        @else
            Entrez le code à 6 chiffres généré par votre application d'authentification.
        @endif
    </p>

    <form wire:submit="authenticate" x-data="throttleCountdown" class="mt-6 space-y-4">
        @if ($usingRecoveryCode)
            <div>
                <label for="recovery_code" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Code de récupération</label>
                <input wire:model="recovery_code" id="recovery_code" type="text" autocomplete="one-time-code" autofocus
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 font-mono text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                @error('recovery_code') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
        @else
            <div>
                <label for="code" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Code</label>
                <input wire:model="code" id="code" type="text" inputmode="numeric" autocomplete="one-time-code" autofocus
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-center font-mono text-lg tracking-[0.4em] text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                @error('code') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
        @endif

        <x-throttle-notice />

        <button type="submit" :disabled="remaining > 0" class="w-full rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400 disabled:pointer-events-none disabled:opacity-50">
            Vérifier
        </button>
    </form>

    <button type="button" wire:click="toggleRecoveryCode" class="mt-5 text-xs text-zinc-500 hover:text-amber-400 transition">
        @if ($usingRecoveryCode)
            ← Utiliser mon application d'authentification
        @else
            Utiliser un code de récupération à la place →
        @endif
    </button>
</div>
