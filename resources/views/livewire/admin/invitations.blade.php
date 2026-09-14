<main class="min-h-screen">
    <div class="mx-auto max-w-4xl px-4 pb-14 sm:px-8 lg:px-12">
        <div class="border-b border-zinc-800 pb-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Administration</p>
            <h1 class="mt-1 font-serif text-3xl font-normal text-zinc-100">Codes d'invitation</h1>
            <p class="mt-2 text-sm text-zinc-500">Génère un code à partager manuellement, ou envoie-le directement par e-mail à la personne invitée.</p>
        </div>

        @include('livewire.partials.notice')

        <div class="mt-6 rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6 shadow-2xl sm:p-8">
            <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-300">Générer un nouveau code</h2>

            <form wire:submit="generate" class="mt-4 grid gap-4 sm:grid-cols-[1fr_auto_auto] sm:items-end">
                <div>
                    <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">
                        E-mail de la personne invitée <span class="normal-case font-normal text-zinc-600">(facultatif)</span>
                    </label>
                    <input wire:model="email" id="email" type="email" placeholder="ami@example.com"
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    @error('email') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    <p class="mt-1.5 text-xs text-zinc-600">Si renseigné, le code est envoyé par e-mail au lieu d'être affiché ici.</p>
                </div>

                <div>
                    <label for="expiresInDays" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Expire dans (jours)</label>
                    <input wire:model="expiresInDays" id="expiresInDays" type="number" min="1" max="365" placeholder="Illimité"
                        class="w-32 rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    @error('expiresInDays') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled"
                    class="h-fit rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400 disabled:opacity-60">
                    <span wire:loading.remove wire:target="generate">Générer</span>
                    <span wire:loading wire:target="generate">Génération…</span>
                </button>
            </form>

            @if ($lastGeneratedCode)
                <div class="mt-5 flex items-center justify-between gap-3 rounded-xl border border-amber-500/40 bg-amber-950/20 px-4 py-3.5">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-amber-500">Code généré — à partager manuellement</p>
                        <p class="mt-1 font-mono text-lg font-bold tracking-wider text-amber-300">{{ $lastGeneratedCode }}</p>
                    </div>
                    <button type="button"
                        x-data
                        x-on:click="navigator.clipboard.writeText('{{ $lastGeneratedCode }}')"
                        class="rounded-xl border border-amber-500/40 px-3.5 py-2 text-xs font-bold uppercase tracking-wide text-amber-300 transition hover:bg-amber-500/10">
                        Copier
                    </button>
                </div>
            @endif
        </div>

        <div class="mt-6 rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6 shadow-2xl sm:p-8">
            <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-300">Codes récents</h2>

            <div class="mt-4 space-y-2">
                @forelse ($this->codes as $invite)
                    @php
                        $isUsed = (bool) $invite->used_at;
                        $isExpired = ! $isUsed && $invite->expires_at && $invite->expires_at->isPast();
                        $isAvailable = ! $isUsed && ! $isExpired;
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3">
                        <div>
                            <p class="font-mono text-sm font-bold tracking-wider text-zinc-200">{{ $invite->code }}</p>
                            <p class="mt-1 text-xs text-zinc-500">
                                @if ($invite->sent_to)
                                    Envoyé à {{ $invite->sent_to }} ·
                                @endif
                                Créé {{ $invite->created_at->diffForHumans() }}
                                @if ($invite->creator)
                                    par {{ $invite->creator->name }}
                                @endif
                                @if ($isUsed)
                                    · Utilisé par {{ $invite->usedByUser?->name ?? 'un compte supprimé' }}
                                @elseif ($invite->expires_at)
                                    · Expire le {{ $invite->expires_at->format('d/m/Y') }}
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($isAvailable)
                                <span class="rounded-full bg-emerald-500/15 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-400">Disponible</span>
                                <button type="button" wire:click="revoke({{ $invite->id }})" wire:confirm="Révoquer ce code ? Il ne pourra plus être utilisé."
                                    class="text-xs font-bold uppercase tracking-wide text-red-400 transition hover:text-red-300">
                                    Révoquer
                                </button>
                            @elseif ($isUsed)
                                <span class="rounded-full bg-zinc-700/50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-zinc-400">Utilisé</span>
                            @else
                                <span class="rounded-full bg-red-500/15 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-red-400">Expiré</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">Aucun code généré pour le moment.</p>
                @endforelse
            </div>
        </div>
    </div>
</main>
