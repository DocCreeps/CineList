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
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-300">Double authentification</h2>
                @if ($this->confirmed)
                    <span class="rounded-full bg-emerald-950/40 px-3 py-1 text-xs font-bold text-emerald-300">Activée</span>
                @endif
            </div>

            <p class="mt-2 text-sm text-zinc-500">
                Ajoute une étape de vérification (application TOTP type Google Authenticator ou Aegis) à la connexion.
            </p>

            @if (! $this->enabled)
                <button wire:click="enable" class="mt-5 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400">
                    Activer la double authentification
                </button>
            @endif

            @if ($showingQrCode && ! $this->confirmed)
                <div class="mt-6 space-y-4 border-t border-zinc-800 pt-6">
                    <p class="text-sm text-zinc-400">Scanne ce QR code avec ton application d'authentification, puis saisis le code généré :</p>

                    <div class="w-fit rounded-xl bg-white p-4">
                        {!! auth()->user()->twoFactorQrCodeSvg() !!}
                    </div>

                    <form wire:submit="confirm" class="flex flex-wrap items-end gap-3">
                        <div>
                            <label for="code" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Code</label>
                            <input wire:model="code" id="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                                class="w-40 rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-center font-mono text-lg tracking-[0.3em] text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                            @error('code') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400">
                            Confirmer
                        </button>
                    </form>
                </div>
            @endif

            @if ($this->confirmed)
                <div class="mt-6 space-y-4 border-t border-zinc-800 pt-6">
                    @if ($showingRecoveryCodes)
                        <div>
                            <p class="text-sm text-zinc-400">
                                Conserve ces codes de récupération dans un endroit sûr : chacun ne peut être utilisé qu'une seule fois pour te connecter si tu perds l'accès à ton application d'authentification.
                            </p>
                            <div class="mt-3 grid grid-cols-2 gap-2 rounded-xl border border-zinc-800 bg-zinc-950 p-4 font-mono text-sm text-zinc-300 sm:grid-cols-3">
                                @foreach (auth()->user()->recoveryCodes() as $recoveryCode)
                                    <span>{{ $recoveryCode }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-3">
                        <button wire:click="regenerateRecoveryCodes" class="rounded-xl border border-zinc-700 px-4 py-2.5 text-sm font-bold text-zinc-300 transition hover:bg-zinc-800/60">
                            Régénérer les codes de récupération
                        </button>
                        <button wire:click="disable" wire:confirm="Désactiver la double authentification ?" class="rounded-xl border border-red-900/60 px-4 py-2.5 text-sm font-bold text-red-400 transition hover:bg-red-950/30">
                            Désactiver
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</main>
