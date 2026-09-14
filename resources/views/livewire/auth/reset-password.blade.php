<div>
    <h1 class="font-display text-3xl tracking-wide text-zinc-100">NOUVEAU MOT DE PASSE</h1>

    <form wire:submit="reset" class="mt-6 space-y-4">
        <div>
            <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">E-mail</label>
            <input wire:model="email" id="email" type="email" autocomplete="username" required autofocus
                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
            @error('email') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Nouveau mot de passe</label>
            <input wire:model="password" id="password" type="password" autocomplete="new-password" required
                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
            <x-password-strength target="password" />
            @error('password') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Confirmer</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" required
                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
        </div>

        <button type="submit" class="w-full rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400">
            Réinitialiser
        </button>
    </form>
</div>
