<main class="min-h-screen">
    <div class="mx-auto max-w-7xl px-4 pb-10 sm:px-8 lg:px-12 lg:pb-14">

        <!-- Introduction de la page -->
        <div class="border-b border-zinc-800 pb-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Sorties cinéma</p>
            <h1 class="mt-1 font-serif text-3xl font-normal text-zinc-100">Les films à l'affiche</h1>
            <p class="mt-2 text-sm text-zinc-500 max-w-lg">Sorties salles françaises, mois par mois. Ajoutez directement à votre liste les films que vous ne voulez pas manquer.</p>
        </div>

        @include('livewire.partials.notice')

        <!-- Navigation calendrier : précédent/suivant + sélecteur pour sauter plus loin -->
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-zinc-800/80 bg-zinc-900/60 px-4 py-3.5">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    wire:click="previousMonth"
                    @disabled(! $canGoPrevious)
                    wire:loading.attr="disabled"
                    class="grid h-9 w-9 place-items-center rounded-xl border border-zinc-700 text-zinc-300 transition hover:border-amber-500/50 hover:text-amber-400 disabled:cursor-not-allowed disabled:opacity-30"
                    aria-label="Mois précédent"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <h2 class="min-w-[10rem] text-center text-lg font-bold text-zinc-100">{{ $monthLabel }}</h2>

                <button
                    type="button"
                    wire:click="nextMonth"
                    @disabled(! $canGoNext)
                    wire:loading.attr="disabled"
                    class="grid h-9 w-9 place-items-center rounded-xl border border-zinc-700 text-zinc-300 transition hover:border-amber-500/50 hover:text-amber-400 disabled:cursor-not-allowed disabled:opacity-30"
                    aria-label="Mois suivant"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <div class="flex items-center gap-2">
                <label for="period" class="text-xs font-bold uppercase tracking-wide text-zinc-500">Aller à</label>
                <select
                    wire:model.live="period"
                    id="period"
                    class="rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                >
                    @foreach ($monthOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($loadError && empty($results))
        <div class="mt-8 rounded-2xl border border-amber-900/60 bg-amber-950/30 p-5 text-amber-200">
            <p class="font-semibold">Sorties indisponibles</p>
            <p class="mt-1 text-sm text-amber-300/80">{{ $loadError }}</p>
        </div>
        @else
        @foreach ($groups as $week => $movies)
        <section class="mt-10" wire:loading.class="opacity-40" wire:target="previousMonth,nextMonth,period">
            <h2 class="mb-5 flex items-center gap-3 border-b border-zinc-800 pb-3 text-xl font-bold tracking-tight text-zinc-100">
                {{ $week }}
                <span class="text-xs font-semibold text-zinc-500 bg-zinc-900 border border-zinc-800 px-2.5 py-1 rounded-full">
                    {{ $movies->count() }} film{{ $movies->count() > 1 ? 's' : '' }}
                </span>
            </h2>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($movies as $movie)
                <article wire:key="upcoming-{{ $movie['tmdb_id'] }}" class="group relative flex gap-3.5 rounded-2xl border border-zinc-800 bg-zinc-900/80 p-3 shadow-lg transition hover:border-amber-500/50 hover:bg-zinc-900">
                    <button wire:click="showDetails('{{ $movie['tmdb_id'] }}')" class="relative h-24 w-16 shrink-0 cursor-pointer overflow-hidden rounded-xl bg-zinc-950 focus:outline-none" aria-label="Détails de {{ $movie['title'] }}">
                        @if($movie['poster_url'])
                        <img src="{{ $movie['poster_url'] }}" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        @else
                        <div class="grid h-full w-full place-items-center text-xs text-zinc-700">N/A</div>
                        @endif
                    </button>
                    <div class="flex min-w-0 flex-1 flex-col justify-between py-0.5">
                        <div>
                            <h3 class="truncate font-bold text-zinc-100 text-sm group-hover:text-amber-400 transition-colors">{{ $movie['title'] }}</h3>
                            <p class="mt-0.5 text-xs text-zinc-500">
                                Sortie le {{ \Illuminate\Support\Carbon::parse($movie['release_date'])->format('d/m/Y') }}
                            </p>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <button wire:click="add('{{ $movie['tmdb_id'] }}', 'cinema')" class="rounded-lg bg-amber-950/80 border border-amber-800/60 px-2.5 py-1 text-[11px] font-bold text-amber-400 transition hover:bg-amber-900 hover:text-white">+ Cinéma</button>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>
        </section>
        @endforeach

        @if (empty($results) && ! $loadError)
        <p class="mt-10 text-sm text-zinc-500">Aucune sortie prévue sur ce mois pour l'instant.</p>
        @endif
        @endif

        @include('livewire.partials.movie-modal')
    </div>
</main>
