<?php

namespace App\Actions\Admin;

use App\Models\WatchlistItem;
use App\Support\Movies\Genres;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ComputeMemberDetail
{
    /**
     * Les films d'un membre (chargés ici, ou passés par l'appelant qui les a déjà) et tout ce qu'on
     * en tire : répartition par statut/source/genre, réalisateurs, notes, temps de visionnage.
     * "watched" et "to_rewatch" comptent tous les deux comme déjà visionnés au moins une fois.
     *
     * @param  Collection<int, WatchlistItem>|null  $items
     */
    public function handle(int $memberId, ?Collection $items = null): array
    {
        $items ??= WatchlistItem::query()
            ->withoutGlobalScope('owner')
            ->where('user_id', $memberId)
            ->get(WatchlistItem::ADMIN_SAFE_COLUMNS);

        $watched = $items->whereIn('status', ['watched', 'to_rewatch']);

        $genreCounts = Genres::count($watched);
        $genreCountsAll = Genres::count($items);

        $rated = $watched->whereNotNull('personal_rating');
        $lastWatched = $watched->sortByDesc('watched_at')->first();
        $toRewatch = $items->where('status', 'to_rewatch');
        $lastActivity = DB::table('sessions')->where('user_id', $memberId)->max('last_activity');

        return [
            // Vue « films vus » (utilisée pour le résumé et les réalisateurs).
            'genreCounts' => $genreCounts,
            'topGenreCount' => $genreCounts->first(),
            'watchedCount' => $watched->count(),
            'toWatchCount' => $items->where('status', 'to_watch')->count(),
            'averageRating' => $rated->isNotEmpty() ? round((float) $rated->avg('personal_rating'), 1) : null,
            'ratedCount' => $rated->count(),
            'lastWatched' => $lastWatched,

            // Vue « toute la liste », avec la part déjà vue de chaque genre.
            'total' => $items->count(),
            'statusCounts' => [
                'to_watch' => $items->where('status', 'to_watch')->count(),
                'watched' => $items->where('status', 'watched')->count(),
                'to_rewatch' => $items->where('status', 'to_rewatch')->count(),
            ],
            'sourceCounts' => [
                'cinema' => $items->where('source', 'cinema')->count(),
                'streaming' => $items->where('source', 'streaming')->count(),
            ],
            // Où ce membre a vu ses films "à revoir" pour la toute première fois (champ distinct de `source`).
            'toRewatchCount' => $toRewatch->count(),
            'toRewatchFirstSeenCounts' => [
                'cinema' => $toRewatch->where('first_watched_source', 'cinema')->count(),
                'streaming' => $toRewatch->where('first_watched_source', 'streaming')->count(),
            ],
            'genreBreakdown' => $genreCountsAll->map(fn ($total, $genre) => [
                'total' => $total,
                'watched' => $genreCounts->get($genre, 0),
            ]),
            'topBreakdownCount' => $genreCountsAll->first(),

            'directors' => $watched->pluck('director')->filter()->countBy()->sortDesc()->take(5),
            'ratingDistribution' => collect(range(1, 5))
                ->mapWithKeys(fn ($stars) => [$stars => $rated->where('personal_rating', $stars)->count()])
                ->all(),

            // La durée est stockée en texte (« 142 min ») : le cast en entier garde le nombre.
            'watchedMinutes' => (int) $watched->sum(fn ($item) => (int) $item->runtime),
            'addedLast30Days' => $items->where('created_at', '>=', now()->subDays(30))->count(),
            'lastActivity' => $lastActivity ? Carbon::createFromTimestamp((int) $lastActivity) : null,
        ];
    }
}
