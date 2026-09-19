<main class="min-h-screen">
    <div class="mx-auto max-w-4xl px-4 pb-14 sm:px-8 lg:px-12">
        <div class="border-b border-zinc-800 pb-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Administration</p>
            <h1 class="mt-1 font-serif text-3xl font-normal text-zinc-100">Codes d'invitation</h1>
            <p class="mt-2 text-sm text-zinc-500">Génère un code à partager manuellement, ou envoie-le directement par e-mail à la personne invitée.</p>
            @include('livewire.admin.partials.tabs')
        </div>

        <div>
            <div class="mx-auto max-w-5xl px-4 py-8">
                {{-- En-tête --}}
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-zinc-100">Codes d'invitation</h1>
                        <p class="mt-1 text-sm text-zinc-400">Générez et gérez les accès d'inscription à la plateforme.</p>
                    </div>
                </div>

                {{-- Formulaire de génération --}}
                <div class="mt-6 rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6 shadow-2xl sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Générer un nouveau code</h2>

                    <form wire:submit="generate" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_140px_120px_auto] lg:items-end">
                        {{-- Email --}}
                        <div>
                            <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">
                                E-mail réservé <span class="normal-case font-normal text-zinc-600">(optionnel)</span>
                            </label>
                            <input wire:model="email" id="email" type="email" placeholder="ami@exemple.fr" class="w-full rounded-xl border border-zinc-700/80 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                            @error('email') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>

                        {{-- Expiration en jours --}}
                        <div>
                            <label for="expiresInDays" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Expiration</label>
                            <input wire:model="expiresInDays" id="expiresInDays" type="number" min="1" max="365" placeholder="Jamais" class="w-full rounded-xl border border-zinc-700/80 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                            @error('expiresInDays') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>

                        {{-- Nombre d'utilisations --}}
                        <div>
                            <label for="maxUses" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Utilisations</label>
                            <input wire:model="maxUses" id="maxUses" type="number" min="1" max="1000" placeholder="1" class="w-full rounded-xl border border-zinc-700/80 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                            @error('maxUses') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>

                        {{-- Bouton Submit --}}
                        <button type="submit" wire:loading.attr="disabled" class="flex h-[42px] items-center justify-center rounded-xl bg-amber-500 px-6 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/30 transition hover:bg-amber-400 active:scale-[0.98] disabled:opacity-60">
                            <span wire:loading.remove wire:target="generate">Générer</span>
                            <span wire:loading wire:target="generate">Génération…</span>
                        </button>
                    </form>

                    @if ($lastGeneratedCode)
                    <div class="mt-6 flex items-center gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4">
                        <span class="text-xs font-semibold text-amber-400">Code généré :</span>
                        <code class="font-mono text-base font-bold tracking-widest text-amber-300">{{ $lastGeneratedCode }}</code>
                    </div>
                    @endif
                </div>

                {{-- Liste des codes générés --}}
                <div class="mt-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Codes récents</h2>

                    <div class="mt-4 space-y-2.5">
                        @forelse ($this->codes as $invite)
                        @php
                        $isExpired = $invite->isExpired();
                        $isExhausted = $invite->isExhausted();
                        $isAvailable = $invite->isAvailable();
                        $canRevoke = $invite->uses_count === 0;
                        // Plus d'utilisation restante : "Utilisé" si le code a servi, "Indisponible" sinon.
                        $exhaustedLabel = $invite->uses_count > 0 ? 'Utilisé' : 'Indisponible';
                        @endphp
                        {{-- Un code inutilisable (épuisé ou expiré) est atténué ; il retrouve toute son opacité au survol. --}}
                        <div @class([
                            'rounded-xl border border-zinc-800/80 bg-zinc-950/70 p-4 transition hover:border-zinc-700/60',
                            'opacity-60 hover:opacity-100' => ! $isAvailable,
                        ])>
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                {{-- Info Code --}}
                                <div class="space-y-1">
                                    <div class="flex items-center gap-3">
                                        <span class="font-mono text-base font-bold tracking-wider text-zinc-100">{{ $invite->code }}</span>

                                        {{-- Badge d'état --}}
                                        @if ($isAvailable)
                                        <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-400 border border-emerald-500/20">Disponible</span>
                                        @elseif ($isExhausted)
                                        <span class="rounded-full bg-zinc-800 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-zinc-400">{{ $exhaustedLabel }}</span>
                                        @else
                                        <span class="rounded-full bg-red-500/10 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-400 border border-red-500/20">Expiré</span>
                                        @endif
                                    </div>

                                    <p class="text-xs text-zinc-500">
                                        @if ($invite->sent_to)
                                        Pour <span class="text-zinc-300">{{ $invite->sent_to }}</span> ·
                                        @endif
                                        Généré {{ $invite->created_at->diffForHumans() }}
                                        @if ($invite->creator)
                                        par <span class="text-zinc-400">{{ $invite->creator->name }}</span>
                                        @endif
                                        · <span class="font-medium text-zinc-300">{{ $invite->uses_count }}/{{ $invite->max_uses ?? '1' }}</span> utilisation(s)
                                        @if ($invite->expires_at)
                                        · Expire le {{ $invite->expires_at->format('d/m/Y') }}
                                        @endif
                                    </p>
                                </div>

                                {{-- Action --}}
                                <div>
                                    @if ($canRevoke && ! $isExpired)
                                    <button
                                        type="button"
                                        x-on:click="$store.confirmModal.open(@js('Révoquer ce code ?'), () => $wire.revoke({{ $invite->id }}))"
                                        class="rounded-lg border border-red-500/20 bg-red-500/5 px-3 py-1.5 text-xs font-semibold text-red-400 transition hover:bg-red-500/15 hover:text-red-300"
                                    >
                                        Révoquer
                                    </button>
                                    @elseif ($isAvailable && $invite->uses_count > 0)
                                    <button
                                        type="button"
                                        x-on:click="$store.confirmModal.open(@js('Désactiver ce code ?'), () => $wire.disable({{ $invite->id }}))"
                                        class="rounded-lg border border-zinc-700 bg-zinc-800/50 px-3 py-1.5 text-xs font-semibold text-zinc-400 transition hover:bg-zinc-800 hover:text-zinc-200"
                                    >
                                        Désactiver
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="rounded-xl border border-dashed border-zinc-800 p-8 text-center text-sm text-zinc-500">
                            Aucun code d'invitation généré pour le moment.
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

</main>
