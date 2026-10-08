<?php

namespace App\Actions\Watchlist;

use App\Enums\WatchSource;
use App\Enums\WatchStatus;
use App\Models\WatchlistItem;
use App\Support\Movies\Favorites;
use App\Support\Movies\FieldList;
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
        $watched = WatchlistItem::query()->whereNotNull('watched_at')->where('status', WatchStatus::Watched->value)->get();
        $toRewatch = WatchlistItem::query()->whereNotNull('watched_at')->where('status', WatchStatus::ToRewatch->value)->get();

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
            ->flatMap(fn ($g) => FieldList::split($g))
            ->countBy()
            ->sortDesc();

        $directorCounts = Favorites::directors($watched);
        $studioCounts = Favorites::studios($watched);
        $rated = $watched->whereNotNull('personal_rating');

        return [
            'totalWatched' => $watched->count(),
            'averageRating' => $rated->isNotEmpty() ? round((float) $rated->avg('personal_rating'), 1) : null,
            'topGenres' => $genreCounts->take(3),
            'cinemaCount' => $watched->where('source', WatchSource::Cinema)->count(),
            'streamingCount' => $watched->where('source', WatchSource::Streaming)->count(),
            'toRewatchCount' => $toRewatch->count(),
            // Où ces films à revoir ont été vus la toute première fois (champ distinct de `source`).
            'toRewatchCinemaCount' => $toRewatch->where('first_watched_source', WatchSource::Cinema)->count(),
            'toRewatchStreamingCount' => $toRewatch->where('first_watched_source', WatchSource::Streaming)->count(),
            'topDirectors' => $directorCounts->take(3),
            'topStudios' => $studioCounts->take(3),
            // Films préférés sous 2 angles (nb de vues seul / note seule) — voir Favorites::filmsForUser().
            'favoriteFilms' => Favorites::filmsForUser($watched->merge($toRewatch), 3),
        ];
    }
}
