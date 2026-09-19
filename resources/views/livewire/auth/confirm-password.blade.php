<div>
    <h1 class="font-display text-3xl tracking-wide text-zinc-100">CONFIRMATION</h1>
    <p class="mt-1 text-sm text-zinc-500">Zone sensible : merci de confirmer votre mot de passe avant de continuer.</p>

    <form wire:submit="confirm" x-data="throttleCountdown" class="mt-6 space-y-4">
        <div>
            <label for="password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Mot de passe</label>
            <x-password-input wire:model="password" id="password" autocomplete="current-password" required autofocus />
            @error('password') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <x-throttle-notice />

        <button type="submit" :disabled="remaining > 0" class="w-full rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400 disabled:pointer-events-none disabled:opacity-50">
            Confirmer
        </button>
    </form>
</div>
