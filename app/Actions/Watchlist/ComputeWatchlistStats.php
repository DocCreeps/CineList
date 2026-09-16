<?php

namespace App\Actions\Watchlist;

use App\Models\WatchlistItem;

class ComputeWatchlistStats
{
    /**
     * "Watched" et "to_rewatch" sont traités séparément : les deux ont un `watched_at`, mais
     * "to_rewatch" ne compte pas comme visionnage terminé (compteurs, genres, note moyenne,
     * frise principale) — il a sa propre frise et son propre compteur.
     */
    public function handle(): array
    {
        $watched = WatchlistItem::query()->whereNotNull('watched_at')->where('status', 'watched')->get();
        $toRewatch = WatchlistItem::query()->whereNotNull('watched_at')->where('status', 'to_rewatch')->get();

        $genreCounts = $watched->pluck('genre')
            ->flatMap(fn ($g) => array_map('trim', explode(',', (string) $g)))
            ->filter()
            ->countBy()
            ->sortDesc();

        $directorCounts = $watched->pluck('director')->filter()->countBy()->sortDesc();

        $rated = $watched->whereNotNull('personal_rating');

        $watchedThisYear = $watched->filter(fn ($item) => $item->watched_at?->year === now()->year);

        $timeline = $watched->sortByDesc('watched_at')
            ->groupBy(fn ($item) => ucfirst($item->watched_at->translatedFormat('F Y')));

        $toRewatchTimeline = $toRewatch->sortByDesc('watched_at')
            ->groupBy(fn ($item) => ucfirst($item->watched_at->translatedFormat('F Y')));

        return [
            'totalWatched' => $watched->count(),
            'watchedThisYear' => $watchedThisYear->count(),
            'toRewatchCount' => $toRewatch->count(),
            'averageRating' => $rated->isNotEmpty() ? round((float) $rated->avg('personal_rating'), 1) : null,
            'topGenre' => $genreCounts->keys()->first(),
            'topGenreCount' => $genreCounts->first(),
            'topDirector' => $directorCounts->keys()->first(),
            'topDirectorCount' => $directorCounts->first(),
            'cinemaCount' => $watched->where('source', 'cinema')->count(),
            'streamingCount' => $watched->where('source', 'streaming')->count(),
            'timeline' => $timeline,
            'toRewatchTimeline' => $toRewatchTimeline,
        ];
    }
}
