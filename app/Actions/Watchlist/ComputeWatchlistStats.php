<?php

namespace App\Actions\Watchlist;

use App\Models\WatchlistItem;
use App\Support\Movies\Favorites;
use Illuminate\Support\Collection;

class ComputeWatchlistStats
{
    /**
     * "Watched" et "to_rewatch" sont traités séparément : les deux ont un `watched_at`, mais
     * "to_rewatch" ne compte pas comme visionnage terminé (compteurs, genres, note moyenne,
     * frise) — il a son propre compteur.
     *
     * Deux blocs identiques : `yearStats` (année en cours uniquement) et `generalStats` (depuis le
     * début), chacun avec ses chiffres clés, ses tops et ses films préférés.
     */
    public function handle(): array
    {
        $watched = WatchlistItem::query()->whereNotNull('watched_at')->where('status', 'watched')->get();
        $toRewatch = WatchlistItem::query()->whereNotNull('watched_at')->where('status', 'to_rewatch')->get();

        $year = now()->year;
        $inYear = fn ($item) => $item->watched_at?->year === $year;

        $general = $this->summarize($watched, $toRewatch);

        return [
            'totalWatched' => $general['totalWatched'],
            'toRewatchCount' => $general['toRewatchCount'],
            'generalStats' => $general,
            'yearStats' => $this->summarize($watched->filter($inYear), $toRewatch->filter($inYear)),
            'timeline' => $watched->sortByDesc('watched_at')
                ->groupBy(fn ($item) => ucfirst($item->watched_at->translatedFormat('F Y'))),
        ];
    }

    /**
     * Les 7 chiffres clés, les tops (réalisateurs, studios) et les films préférés d'un jeu de films
     * (année en cours ou ensemble).
     *
     * @param  Collection<int, WatchlistItem>  $watched
     * @param  Collection<int, WatchlistItem>  $toRewatch
     */
    private function summarize(Collection $watched, Collection $toRewatch): array
    {
        $genreCounts = $watched->pluck('genre')
            ->flatMap(fn ($g) => array_map('trim', explode(',', (string) $g)))
            ->filter()
            ->countBy()
            ->sortDesc();

        $directorCounts = Favorites::directors($watched);
        $studioCounts = Favorites::studios($watched);
        $rated = $watched->whereNotNull('personal_rating');

        return [
            'totalWatched' => $watched->count(),
            'averageRating' => $rated->isNotEmpty() ? round((float) $rated->avg('personal_rating'), 1) : null,
            'topGenres' => $genreCounts->take(3),
            'cinemaCount' => $watched->where('source', 'cinema')->count(),
            'streamingCount' => $watched->where('source', 'streaming')->count(),
            'toRewatchCount' => $toRewatch->count(),
            // Où ces films à revoir ont été vus la toute première fois (champ distinct de `source`).
            'toRewatchCinemaCount' => $toRewatch->where('first_watched_source', 'cinema')->count(),
            'toRewatchStreamingCount' => $toRewatch->where('first_watched_source', 'streaming')->count(),
            'topDirectors' => $directorCounts->take(3),
            'topStudios' => $studioCounts->take(3),
            // Films préférés sous 2 angles (nb de vues seul / note seule) — voir Favorites::filmsForUser().
            'favoriteFilms' => Favorites::filmsForUser($watched->merge($toRewatch), 3),
        ];
    }
}
