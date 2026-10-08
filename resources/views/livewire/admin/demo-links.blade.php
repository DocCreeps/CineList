<main class="min-h-screen">
    <div class="mx-auto max-w-4xl px-4 pb-14 sm:px-8 lg:px-12">
        <div class="border-b border-zinc-800 pb-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Administration</p>
            <h1 class="font-display mt-1 text-4xl tracking-wide text-zinc-100">Liens démo</h1>
            <p class="mt-2 text-sm text-zinc-500">Chaque visite d'un lien ouvre un compte fictif jetable (copie du compte modèle) : le visiteur peut tout faire sans toucher à tes données, et son espace est supprimé après {{ config('demo.sandbox_ttl_hours') }} h.</p>
            @include('livewire.admin.partials.tabs')
        </div>

        <div class="mx-auto max-w-5xl px-4 py-8">
            @unless (config('demo.enabled'))
            <div class="mb-6 rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-300">
                Les liens démo sont désactivés (<code class="font-mono">DEMO_ENABLED=false</code>) : tous les liens ci-dessous renvoient une erreur 404.
            </div>
            @endunless

            @unless ($this->demoUserExists)
            <div class="mb-6 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-300">
                Aucun compte modèle pour le moment. Crée-le avec <code class="font-mono">php artisan demo:create --from=ton@email.fr</code>.
            </div>
            @endunless

            <p class="mb-6 text-xs text-zinc-500">
                <span class="font-semibold text-zinc-300">{{ $this->activeSandboxes }}</span> espace(s) de démo actif(s) sur {{ config('demo.max_sandboxes') }} maximum.
            </p>

            {{-- Formulaire de création --}}
            <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6 shadow-2xl sm:p-8">
                <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Créer un nouveau lien</h2>

                <form wire:submit="generate" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_140px_auto] lg:items-end">
                    <div>
                        <label for="label" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">
                            Nom du lien <span class="normal-case font-normal text-zinc-600">(optionnel)</span>
                        </label>
                        <input wire:model="label" id="label" type="text" maxlength="100" placeholder="CV, LinkedIn, recruteur X…" class="w-full rounded-xl border border-zinc-700/80 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                        @error('label') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="expiresInDays" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Expiration</label>
                        <input wire:model="expiresInDays" id="expiresInDays" type="number" min="1" max="365" placeholder="Jamais" class="w-full rounded-xl border border-zinc-700/80 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                        @error('expiresInDays') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" class="flex h-[42px] items-center justify-center rounded-xl bg-amber-500 px-6 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/30 transition hover:bg-amber-400 active:scale-[0.98] disabled:opacity-60">
                        <span wire:loading.remove wire:target="generate">Créer</span>
                        <span wire:loading wire:target="generate">Création…</span>
                    </button>
                </form>

                @if ($lastGeneratedUrl)
                <div class="mt-6 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4" x-data="copyButton">
                    <p class="text-xs font-semibold text-amber-400">Lien créé :</p>
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <code class="break-all font-mono text-sm text-amber-300">{{ $lastGeneratedUrl }}</code>
                        <button type="button"
                            x-on:click="copy(@js($lastGeneratedUrl))"
                            class="rounded-lg border border-amber-500/30 px-3 py-1.5 text-xs font-semibold text-amber-300 transition hover:bg-amber-500/15">
                            <span x-show="! copied">Copier</span>
                            <span x-show="copied" x-cloak>Copié !</span>
                        </button>
                    </div>
                </div>
                @endif
            </div>

            {{-- Liste des liens --}}
            <div class="mt-8">
                <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Liens créés</h2>

                <div class="mt-4 space-y-2.5">
                    @forelse ($this->links as $link)
                    <div x-data="copyButton" @class([
                        'rounded-xl border border-zinc-800/80 bg-zinc-950/70 p-4 transition hover:border-zinc-700/60',
                        'opacity-60 hover:opacity-100' => ! $link->isActive(),
                    ])>
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="min-w-0 space-y-1">
                                <div class="flex items-center gap-3">
                                    <span class="text-base font-bold text-zinc-100">{{ $link->label ?? 'Lien sans nom' }}</span>

                                    @if ($link->isRevoked())
                                    <span class="rounded-full bg-zinc-800 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-zinc-400">Révoqué</span>
                                    @elseif ($link->isExpired())
                                    <span class="rounded-full border border-red-500/20 bg-red-500/10 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-400">Expiré</span>
                                    @else
                                    <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-400">Actif</span>
                                    @endif
                                </div>

                                <p class="text-xs text-zinc-500">
                                    Créé {{ $link->created_at->diffForHumans() }}
                                    @if ($link->creator)
                                    par <span class="text-zinc-400">{{ $link->creator->name }}</span>
                                    @endif
                                    · <span class="font-medium text-zinc-300">{{ $link->uses_count }}</span> connexion(s)
                                    @if ($link->last_used_at)
                                    · dernière {{ $link->last_used_at->diffForHumans() }}
                                    @endif
                                    @if ($link->expires_at)
                                    · {{ $link->isExpired() ? 'Expiré' : 'Expire' }} le {{ $link->expires_at->format('d/m/Y') }}
                                    @endif
                                </p>

                                @if ($link->isActive())
                                <code class="block truncate font-mono text-xs text-zinc-500">{{ $link->url() }}</code>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($link->isActive())
                                <button type="button"
                                    x-on:click="copy(@js($link->url()))"
                                    class="rounded-lg border border-zinc-700 bg-zinc-800/50 px-3 py-1.5 text-xs font-semibold text-zinc-300 transition hover:bg-zinc-800 hover:text-zinc-100">
                                    <span x-show="! copied">Copier le lien</span>
                                    <span x-show="copied" x-cloak>Copié !</span>
                                </button>
                                <button type="button"
                                    x-on:click="$store.confirmModal.open(@js('Révoquer ce lien ?'), $wire, 'revoke', [{{ $link->id }}])"
                                    class="rounded-lg border border-red-500/20 bg-red-500/5 px-3 py-1.5 text-xs font-semibold text-red-400 transition hover:bg-red-500/15 hover:text-red-300">
                                    Révoquer
                                </button>
                                @else
                                <button type="button"
                                    x-on:click="$store.confirmModal.open(@js('Supprimer définitivement ce lien ?'), $wire, 'delete', [{{ $link->id }}])"
                                    class="rounded-lg border border-zinc-700 bg-zinc-800/50 px-3 py-1.5 text-xs font-semibold text-zinc-400 transition hover:bg-zinc-800 hover:text-zinc-200">
                                    Supprimer
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="rounded-xl border border-dashed border-zinc-800 p-8 text-center text-sm text-zinc-500">
                        Aucun lien démo créé pour le moment.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</main>
