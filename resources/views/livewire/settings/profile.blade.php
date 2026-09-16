<main class="min-h-screen">
    <div class="mx-auto max-w-2xl px-4 pb-14 sm:px-8 lg:px-12">
        <div class="border-b border-zinc-800 pb-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Paramètres</p>
            <h1 class="mt-1 font-serif text-3xl font-normal text-zinc-100">Mon compte</h1>
        </div>

        <div class="mt-6">
            <x-settings-nav />
        </div>

        @include('livewire.partials.notice')

        <div class="mt-6 rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6 shadow-2xl sm:p-8">
            <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-300">Informations du profil</h2>

            <form wire:submit="update" class="mt-5 space-y-4">
                <div>
                    <label for="name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Nom</label>
                    <input wire:model="name" id="name" type="text" required
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    @error('name', 'updateProfileInformation') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">E-mail</label>
                    <input wire:model="email" id="email" type="email" required
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    @error('email', 'updateProfileInformation') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    <p class="mt-1.5 text-xs text-zinc-500">Changer d'adresse e-mail nécessite de la revérifier.</p>
                </div>

                <button type="submit" class="rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400">
                    Enregistrer
                </button>
            </form>
        </div>

        <div class="mt-6 rounded-2xl border border-red-900/50 bg-red-950/10 p-6 shadow-2xl sm:p-8">
            <h2 class="text-sm font-bold uppercase tracking-wide text-red-400">Zone de danger</h2>
            <p class="mt-2 text-sm text-zinc-500">Supprime définitivement ton compte et tous tes films (à voir, vus, à revoir). Cette action est irréversible.</p>

            <div class="mt-5">
                @if (! $confirmingDeletion)
                    <button wire:click="confirmDeletion" class="rounded-xl border border-red-900/60 px-4 py-2.5 text-sm font-bold text-red-400 transition hover:bg-red-950/30">
                        Supprimer mon compte
                    </button>
                @else
                    <form wire:submit="deleteAccount" class="max-w-sm space-y-3">
                        <div>
                            <label for="delete-password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Confirme ton mot de passe</label>
                            <input wire:model="password" id="delete-password" type="password" autocomplete="current-password" required autofocus
                                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500">
                            @error('password') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg transition hover:bg-red-500">
                                Supprimer définitivement
                            </button>
                            <button type="button" wire:click="cancelDeletion" class="rounded-xl border border-zinc-700 px-4 py-2.5 text-sm font-bold text-zinc-300 transition hover:bg-zinc-800/60">
                                Annuler
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</main>
