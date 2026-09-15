<?php

namespace App\Actions\Admin;

use App\Models\WatchlistItem;

class ComputeMemberDetail
{
    /**
     * Détail par catégorie (genre) pour un membre donné, calculé uniquement sur ses films vus
     * ("watched" et "to_rewatch" comptent tous les deux comme déjà visionnés au moins une fois).
     */
    public function handle(int $memberId): array
    {
        $items = WatchlistItem::query()
            ->withoutGlobalScope('owner')
            ->where('user_id', $memberId)
            ->get();

        $watched = $items->whereIn('status', ['watched', 'to_rewatch']);

        $genreCounts = $watched->pluck('genre')
            ->flatMap(fn ($genre) => array_map('trim', explode(',', (string) $genre)))
            ->filter()
            ->countBy()
            ->sortDesc();

        $rated = $watched->whereNotNull('personal_rating');

        $lastWatched = $watched->sortByDesc('watched_at')->first();

        return [
            'genreCounts' => $genreCounts,
            'topGenreCount' => $genreCounts->first(),
            'watchedCount' => $watched->count(),
            'toWatchCount' => $items->where('status', 'to_watch')->count(),
            'averageRating' => $rated->isNotEmpty() ? round((float) $rated->avg('personal_rating'), 1) : null,
            'lastWatched' => $lastWatched,
        ];
    }
}
