<main class="min-h-screen">
    <div class="mx-auto max-w-7xl px-4 pb-10 sm:px-8 lg:px-12 lg:pb-14">

        <!-- Introduction de la page -->
        <div class="border-b border-zinc-800 pb-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Sorties cinéma</p>
            <h1 class="mt-1 font-serif text-3xl font-normal text-zinc-100">Les films à l'affiche</h1>
            <p class="mt-2 text-sm text-zinc-500 max-w-lg">Sorties salles françaises, semaine par semaine : la semaine en cours, puis les 3 mois passés et les 3 mois à venir. Ajoutez directement à votre liste les films que vous ne voulez pas manquer.</p>
        </div>

        <!-- Bloc fixe : la semaine cinéma en cours (du mercredi au mardi) -->
        <section class="mt-8">
            @include('livewire.upcoming.partials.section', [
                'title' => 'Cette semaine',
                'range' => $weekRange,
                'section' => $week,
                'inList' => $inList,
                'keyPrefix' => 'week',
                'showStatus' => true,
                'empty' => 'Aucune sortie répertoriée cette semaine.',
            ])
        </section>

        <!-- Explorateur : une autre semaine à la fois, de -3 à +3 mois -->
        @if(! $browsed)
        {{-- « Cette semaine » est déjà affichée : l'explorateur se charge juste après, sans bloquer la page. --}}
        <section class="mt-12" wire:init="loadBrowser">
            <div class="mb-5 flex items-end justify-between border-b border-zinc-800 pb-3">
                <h2 class="text-xl font-bold tracking-tight text-zinc-100">{{ $browsedLabel }}</h2>
            </div>
            <p class="flex items-center gap-2 text-sm text-zinc-500">
                <svg class="h-4 w-4 shrink-0 animate-spin text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M21 12a9 9 0 1 1-6.22-8.56" />
                </svg>
                Chargement des autres semaines…
            </p>
        </section>
        @else
        <section class="mt-12">
            <div class="mb-5 flex items-end justify-between border-b border-zinc-800 pb-3">
                <h2 class="text-xl font-bold tracking-tight text-zinc-100">{{ $browsedLabel }}</h2>
                <p class="text-xs font-semibold text-zinc-500">3 mois en arrière, 3 mois en avant</p>
            </div>

            <!-- Navigation : semaine précédente / suivante + saut direct -->
            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-zinc-800/80 bg-zinc-900/40 p-2.5">
                <div class="inline-flex items-stretch overflow-hidden rounded-xl border border-zinc-800 bg-zinc-950/70">
                    <button
                        type="button"
                        wire:click="previousWeek"
                        @disabled(! $canGoPrevious)
                        wire:loading.attr="disabled"
                        wire:target="previousWeek,nextWeek,week"
                        class="grid h-11 w-11 shrink-0 place-items-center text-zinc-400 transition hover:bg-zinc-800/70 hover:text-amber-400 disabled:cursor-not-allowed disabled:opacity-20 disabled:hover:bg-transparent disabled:hover:text-zinc-400"
                        aria-label="Semaine précédente"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <div class="flex min-w-[12.5rem] items-center justify-center gap-2 border-x border-zinc-800 px-4">
                        <svg class="h-4 w-4 shrink-0 text-amber-500/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" />
                        </svg>
                        <span class="text-sm font-bold tabular-nums text-zinc-100">{{ $browsedRange }}</span>
                        <svg wire:loading wire:target="previousWeek,nextWeek,week" class="h-3.5 w-3.5 shrink-0 animate-spin text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M21 12a9 9 0 1 1-6.22-8.56" />
                        </svg>
                    </div>

                    <button
                        type="button"
                        wire:click="nextWeek"
                        @disabled(! $canGoNext)
                        wire:loading.attr="disabled"
                        wire:target="previousWeek,nextWeek,week"
                        class="grid h-11 w-11 shrink-0 place-items-center text-zinc-400 transition hover:bg-zinc-800/70 hover:text-amber-400 disabled:cursor-not-allowed disabled:opacity-20 disabled:hover:bg-transparent disabled:hover:text-zinc-400"
                        aria-label="Semaine suivante"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <div class="inline-flex items-center gap-2.5 pl-1">
                    <label for="semaine" class="hidden text-xs font-bold uppercase tracking-wide text-zinc-500 sm:block">Aller à</label>
                    <div class="relative">
                        <select
                            wire:model.live="week"
                            id="semaine"
                            class="h-11 appearance-none rounded-xl border border-zinc-700 bg-zinc-950 py-2 pl-3.5 pr-9 text-sm font-bold text-zinc-100 transition hover:border-zinc-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                        >
                            @foreach($weekOptions as $group => $options)
                            <optgroup label="{{ $group }}">
                                @foreach($options as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <svg class="pointer-events-none absolute right-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                @include('livewire.upcoming.partials.section', [
                    'title' => $browsedTitle,
                    'section' => $browsed,
                    'inList' => $inList,
                    'keyPrefix' => 'browsed',
                    'loadingTarget' => 'previousWeek,nextWeek,week',
                    'empty' => 'Aucune sortie répertoriée sur cette semaine.',
                ])
            </div>
        </section>
        @endif

        @include('livewire.partials.movie-modal')
    </div>
</main>
