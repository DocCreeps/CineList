<main class="min-h-screen">
    <div class="mx-auto max-w-6xl px-4 pb-14 sm:px-8 lg:px-12">
        <div class="border-b border-zinc-800 pb-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Administration</p>
            <h1 class="mt-1 font-serif text-3xl font-normal text-zinc-100">Membres & catégories</h1>
            <p class="mt-2 text-sm text-zinc-500">Vue d'ensemble des comptes et de la répartition des films par genre, tous utilisateurs confondus.</p>
            @include('livewire.admin.partials.tabs')
        </div>

        @include('livewire.partials.notice')

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">
            <!-- Membres -->
            <section class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6 shadow-2xl sm:p-8">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-300">Membres</h2>
                    <span class="rounded-full bg-zinc-800 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-zinc-400">{{ $members->count() }} compte{{ $members->count() > 1 ? 's' : '' }}</span>
                </div>
                <p class="mt-1.5 text-xs text-zinc-500">Clique sur un membre pour voir le détail de ses catégories de films.</p>

                <div class="mt-4 space-y-2">
                    @foreach ($members as $member)
                    <div class="rounded-xl border border-zinc-800 bg-zinc-950 transition {{ $selectedMemberId === $member->id ? 'border-amber-500/50' : '' }}">
                        <button
                            type="button"
                            wire:click="toggleMember({{ $member->id }})"
                            wire:key="member-{{ $member->id }}"
                            class="flex w-full flex-wrap items-center justify-between gap-3 px-4 py-3 text-left"
                        >
                            <div class="min-w-0">
                                <p class="flex items-center gap-2 text-sm font-bold text-zinc-200">
                                    {{ $member->name }}
                                    @if ($member->isAdmin())
                                    <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-amber-400">Admin</span>
                                    @endif
                                </p>
                                <p class="mt-1 truncate text-xs text-zinc-500">{{ $member->email }} · Inscrit {{ $member->created_at->diffForHumans() }}</p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <span class="rounded-full bg-zinc-800 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-zinc-300">
                                    {{ $member->watchlist_items_count }} film{{ $member->watchlist_items_count > 1 ? 's' : '' }}
                                </span>
                                <svg class="h-4 w-4 text-zinc-500 transition-transform {{ $selectedMemberId === $member->id ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>

                        @if ($member->id !== auth()->id())
                        <div class="flex justify-end border-t border-zinc-800/70 px-4 py-2">
                            <button
                                type="button"
                                x-on:click="$store.confirmModal.open(@js('Supprimer définitivement « ' . $member->name . ' » ? Tous ses films seront également supprimés. Cette action est irréversible.'), () => $wire.deleteMember({{ $member->id }}))"
                                class="text-[11px] font-bold uppercase tracking-wide text-red-400 transition hover:text-red-300"
                            >
                                Supprimer ce membre
                            </button>
                        </div>
                        @endif

                        @if ($selectedMemberId === $member->id && $selectedMemberDetail)
                        <div class="border-t border-zinc-800 px-4 py-4">
                            <div class="flex flex-wrap gap-x-5 gap-y-1.5 text-xs text-zinc-400">
                                <span><span class="font-bold text-zinc-200">{{ $selectedMemberDetail['watchedCount'] }}</span> vu{{ $selectedMemberDetail['watchedCount'] > 1 ? 's' : '' }}</span>
                                <span><span class="font-bold text-zinc-200">{{ $selectedMemberDetail['toWatchCount'] }}</span> à voir</span>
                                @if ($selectedMemberDetail['averageRating'])
                                <span>Note moyenne <span class="font-bold text-zinc-200">{{ $selectedMemberDetail['averageRating'] }}/10</span></span>
                                @endif
                                @if ($selectedMemberDetail['lastWatched'])
                                <span>Dernier vu <span class="font-bold text-zinc-200">{{ $selectedMemberDetail['lastWatched']->title }}</span></span>
                                @endif
                            </div>

                            @if ($selectedMemberDetail['genreCounts']->isEmpty())
                            <p class="mt-3 text-xs text-zinc-500">Aucun film vu avec un genre renseigné.</p>
                            @else
                            <div class="mt-3 space-y-2.5">
                                @foreach ($selectedMemberDetail['genreCounts'] as $genre => $count)
                                <div>
                                    <div class="mb-1 flex items-baseline justify-between text-xs">
                                        <span class="font-bold text-zinc-300">{{ $genre }}</span>
                                        <span class="text-zinc-500">{{ $count }} film{{ $count > 1 ? 's' : '' }}</span>
                                    </div>
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-800">
                                        <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-red-600" style="width: {{ $selectedMemberDetail['topGenreCount'] ? round($count / $selectedMemberDetail['topGenreCount'] * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </section>

            <!-- Films par catégorie (genre), tous membres confondus -->
            <section class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6 shadow-2xl sm:p-8">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-300">Films par catégorie</h2>
                    <span class="rounded-full bg-zinc-800 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-zinc-400">{{ $totalFilms }} film{{ $totalFilms > 1 ? 's' : '' }} au total</span>
                </div>

                @if ($genreCounts->isEmpty())
                <p class="mt-4 text-sm text-zinc-500">Aucun genre renseigné pour le moment.</p>
                @else
                <div class="mt-5 space-y-3">
                    @foreach ($genreCounts as $genre => $count)
                    <div>
                        <div class="mb-1 flex items-baseline justify-between text-xs">
                            <span class="font-bold text-zinc-300">{{ $genre }}</span>
                            <span class="text-zinc-500">{{ $count }} film{{ $count > 1 ? 's' : '' }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-800">
                            <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-red-600" style="width: {{ $topGenreCount ? round($count / $topGenreCount * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </section>
        </div>
    </div>
</main>
