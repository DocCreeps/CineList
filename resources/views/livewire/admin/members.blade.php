<main class="min-h-screen">
    <div class="mx-auto max-w-6xl px-4 pb-14 sm:px-8 lg:px-12">
        <div class="border-b border-zinc-800 pb-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Administration</p>
            <h1 class="font-display mt-1 text-4xl tracking-wide text-zinc-100">Membres & catégories</h1>
            <p class="mt-2 text-sm text-zinc-500">Les comptes, leur activité et la répartition des films par genre. Ouvrez la fiche d'un membre pour voir ses statistiques et sa liste de films.</p>
            @include('livewire.admin.partials.tabs')
        </div>

        <!-- Membres : réservé à l'admin, ne figure pas sur la page « Bilan » publique -->
        <section class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="cine-card p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Membres</p>
                <p class="mt-1 text-3xl font-black tracking-tight text-zinc-100">{{ $members->count() }}</p>
                <p class="mt-0.5 text-xs text-zinc-500">dont {{ $adminCount }} administrateur{{ $adminCount > 1 ? 's' : '' }}</p>
            </div>
        </section>

        <!-- Statistiques agrégées, identiques à celles de la page « Bilan » publique — voir x-community-stats. -->
        <x-community-stats
            class="mt-3"
            :total-films="$totalFilms"
            :watched-total="$watchedTotal"
            :genre-counts="$genreCounts"
            :top-genre-count="$topGenreCount"
            :top-directors="$topDirectors"
            :top-studios="$topStudios"
            :favorite-films="$favoriteFilms"
            :most-anticipated="$mostAnticipated"
            :cinema-count="$cinemaCount"
            :streaming-count="$streamingCount"
        />

        <!-- Membres : une carte par compte -->
        <section class="mt-8">
            <div class="mb-4 flex items-center justify-between border-b border-zinc-800 pb-3">
                <h2 class="text-xl font-bold tracking-tight text-zinc-100">Membres</h2>
                <span class="rounded-full bg-zinc-800 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-zinc-400">{{ $members->count() }} compte{{ $members->count() > 1 ? 's' : '' }}</span>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($members as $member)
                @php($stats = $memberStats[$member->id])
                <article wire:key="member-{{ $member->id }}" class="flex flex-col rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-5 transition hover:border-zinc-700">
                    <div class="flex items-start gap-3.5">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-amber-500 to-red-600 text-lg font-black text-white ring-1 ring-amber-400/30" aria-hidden="true">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-2 text-base font-bold text-zinc-100">
                                <span class="truncate">{{ $member->name }}</span>
                                @if ($member->isAdmin())
                                <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-amber-400">Admin</span>
                                @endif
                                @if ($member->id === auth()->id())
                                <span class="rounded-full bg-zinc-800 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-zinc-400">Vous</span>
                                @endif
                            </p>
                            <p class="mt-0.5 truncate text-xs text-zinc-500">{{ $member->email }}</p>
                            <p class="mt-0.5 text-[11px] text-zinc-600">
                                Inscrit le {{ $member->created_at->copy()->locale('fr')->translatedFormat('j F Y') }}
                                @if ($stats['lastActivity'])
                                · Actif {{ $stats['lastActivity']->copy()->locale('fr')->diffForHumans() }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <dl class="mt-4 grid grid-cols-3 divide-x divide-zinc-800 rounded-xl border border-zinc-800 bg-zinc-950/60 text-center">
                        <div class="px-2 py-2.5">
                            <dd class="text-lg font-black text-zinc-100">{{ $member->watchlist_items_count }}</dd>
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Films</dt>
                        </div>
                        <div class="px-2 py-2.5">
                            <dd class="text-lg font-black text-emerald-400">{{ $stats['watched'] }}</dd>
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Vus</dt>
                        </div>
                        <div class="px-2 py-2.5">
                            <dd class="text-lg font-black text-amber-400">{{ $stats['toWatch'] }}</dd>
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">À voir</dt>
                        </div>
                    </dl>

                    <div class="mt-3 flex min-h-[1.75rem] flex-wrap items-center gap-1.5">
                        @forelse ($stats['topGenres'] as $genre)
                        <span class="rounded-full border border-zinc-700/70 bg-zinc-800/60 px-2.5 py-0.5 text-[11px] font-semibold text-zinc-300">{{ $genre }}</span>
                        @empty
                        <span class="text-xs text-zinc-600">Aucun genre renseigné.</span>
                        @endforelse
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-zinc-800/70 pt-3">
                        <a href="{{ route('admin.members.show', $member) }}" wire:navigate class="text-xs font-bold uppercase tracking-wide text-amber-400 transition hover:text-amber-300">Voir la fiche & les films →</a>

                        @if ($member->id !== auth()->id())
                        <button
                            type="button"
                            x-on:click="$store.confirmModal.open(@js('Supprimer définitivement « ' . $member->name . ' » ? Tous ses films seront également supprimés. Cette action est irréversible.'), $wire, 'deleteMember', [{{ $member->id }}])"
                            class="text-[11px] font-bold uppercase tracking-wide text-red-400 transition hover:text-red-300"
                        >Supprimer</button>
                        @endif
                    </div>
                </article>
                @endforeach
            </div>
        </section>
    </div>
</main>
