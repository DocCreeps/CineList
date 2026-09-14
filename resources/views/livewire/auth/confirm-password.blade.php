<div>
    <h1 class="font-display text-3xl tracking-wide text-zinc-100">CONFIRMATION</h1>
    <p class="mt-1 text-sm text-zinc-500">Zone sensible : merci de confirmer votre mot de passe avant de continuer.</p>

    <form wire:submit="confirm" class="mt-6 space-y-4">
        <div>
            <label for="password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Mot de passe</label>
            <input wire:model="password" id="password" type="password" autocomplete="current-password" required autofocus
                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
            @error('password') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="w-full rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400">
            Confirmer
        </button>
    </form>
</div>
