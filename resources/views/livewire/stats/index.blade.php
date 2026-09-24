<main class="min-h-screen">
    <div class="mx-auto max-w-7xl px-4 pb-10 sm:px-8 lg:px-12 lg:pb-14">

        <div class="border-b border-zinc-800 pb-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Statistiques</p>
            <h1 class="font-display mt-1 text-4xl tracking-wide text-zinc-100">Bilan</h1>
        </div>

        @if($totalWatched === 0 && $toRewatchCount === 0)
        <div class="mt-8 rounded-3xl border border-dashed border-zinc-800 bg-zinc-900/30 px-6 py-16 text-center">
            <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-zinc-800 text-xl text-amber-400 font-bold">📊</div>
            <h3 class="mt-4 text-base font-bold text-zinc-100">Aucun film marqué comme vu pour le moment.</h3>
            <p class="mt-1 text-sm text-zinc-500">Vos statistiques apparaîtront ici dès que vous aurez coché vos premiers films.</p>
        </div>
        @else
        <!-- Année en cours (à gauche) et total (à côté) : chacun avec ses chiffres, ses tops et ses films préférés -->
        <div class="mt-8 grid items-start gap-10 xl:grid-cols-2 xl:gap-8">
            <x-personal-bilan
                scope="year"
                eyebrow="Année en cours"
                :title="'Mon année ciné '.now()->year"
                watched-hint="depuis le 1er janvier."
                :stats="$yearStats"
            />
            <x-personal-bilan
                scope="general"
                eyebrow="Total"
                title="Depuis le début"
                watched-hint="au total."
                :stats="$generalStats"
            />
        </div>
        @endif

        <!-- Bilan collectif : replié par défaut. Agrégé, tous membres confondus, sans détail nominatif —
             ce détail (liste des comptes) reste réservé à la page Admin > Membres. -->
        <section class="mt-10 rounded-2xl border border-zinc-800 bg-zinc-900/40" x-data="{ open: false }">
            <button
                type="button"
                x-on:click="open = ! open"
                x-bind:aria-expanded="open.toString()"
                class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left"
            >
                <span>
                    <span class="block text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Communauté</span>
                    <span class="font-display mt-1 block text-2xl tracking-wide text-zinc-100">Bilan collectif</span>
                    <span class="mt-1 block text-xs text-zinc-500">Tous les membres de Cinélist confondus.</span>
                </span>
                <svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 shrink-0 text-zinc-500 transition-transform duration-200" x-bind:class="open ? 'rotate-180' : ''" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
            </button>
            <div x-show="open" x-cloak x-transition.opacity.duration.200ms class="border-t border-zinc-800 px-5 pb-5 pt-5">
                <x-community-stats
                    :total-films="$community['totalFilms']"
                    :watched-total="$community['watchedTotal']"
                    :genre-counts="$community['genreCounts']"
                    :top-genre-count="$community['topGenreCount']"
                    :top-directors="$community['topDirectors']"
                    :top-studios="$community['topStudios']"
                    :favorite-films="$community['favoriteFilms']"
                    :most-anticipated="$community['mostAnticipated']"
                    :to-rewatch-total="$community['toRewatchTotal']"
                    :to-rewatch-first-seen-counts="$community['toRewatchFirstSeenCounts']"
                />
            </div>
        </section>

        {{-- Historique des films vus : replié par défaut, navigation mois par mois (voir x-month-slider).
             Les films « à revoir » n'apparaissent pas sur cette page (uniquement leur compteur). --}}
        @if($totalWatched > 0)
        <x-month-slider class="mt-4" title="Historique" accent="amber" :timeline="$timeline" />
        @endif
    </div>
</main>
