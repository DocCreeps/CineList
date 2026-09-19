<main class="min-h-screen">
    <div class="mx-auto max-w-2xl px-4 pb-14 sm:px-8 lg:px-12">
        <div class="border-b border-zinc-800 pb-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Paramètres</p>
            <h1 class="mt-1 font-serif text-3xl font-normal text-zinc-100">Mon compte</h1>
        </div>

        <div class="mt-6">
            <x-settings-nav />
        </div>

        <div class="mt-6 rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6 shadow-2xl sm:p-8">
            <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-300">Changer de mot de passe</h2>

            <form wire:submit="update" class="mt-5 space-y-4">
                <div>
                    <label for="current_password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Mot de passe actuel</label>
                    <x-password-input wire:model="current_password" id="current_password" autocomplete="current-password" required />
                    @error('current_password', 'updatePassword') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Nouveau mot de passe</label>
                    <x-password-input wire:model="password" id="password" autocomplete="new-password" required />
                    <x-password-strength target="password" />
                    @error('password', 'updatePassword') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Confirmer</label>
                    <x-password-input wire:model="password_confirmation" id="password_confirmation" autocomplete="new-password" required />
                </div>

                <button type="submit" class="rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400">
                    Mettre à jour
                </button>
            </form>
        </div>
    </div>
</main>
