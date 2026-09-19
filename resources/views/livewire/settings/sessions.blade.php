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
            <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-300">Sessions actives</h2>
            <p class="mt-2 text-sm text-zinc-500">Les appareils actuellement connectés à ton compte.</p>

            <div class="mt-5 space-y-3">
                @foreach ($this->sessions as $session)
                    <div class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-zinc-200">
                                {{ $session->browser }} — {{ $session->platform }}
                                @if ($session->is_current_device)
                                    <span class="ml-2 rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-400">Cet appareil</span>
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-zinc-500">{{ $session->ip_address }} · actif {{ $session->last_active }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 border-t border-zinc-800 pt-6">
                @if (! $confirmingLogout)
                    <button wire:click="confirmLogout" class="rounded-xl border border-red-900/60 px-4 py-2.5 text-sm font-bold text-red-400 transition hover:bg-red-950/30">
                        Se déconnecter des autres appareils
                    </button>
                @else
                    <form wire:submit="logoutOtherDevices" x-data="throttleCountdown" class="max-w-sm space-y-3">
                        <div>
                            <label for="password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Confirme ton mot de passe</label>
                            <x-password-input wire:model="password" id="password" autocomplete="current-password" required autofocus />
                            @error('password') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <x-throttle-notice />

                        <div class="flex gap-3">
                            <button type="submit" :disabled="remaining > 0" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg transition hover:bg-red-500 disabled:pointer-events-none disabled:opacity-50">
                                Confirmer la déconnexion
                            </button>
                            <button type="button" wire:click="$set('confirmingLogout', false)" class="rounded-xl border border-zinc-700 px-4 py-2.5 text-sm font-bold text-zinc-300 transition hover:bg-zinc-800/60">
                                Annuler
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</main>
