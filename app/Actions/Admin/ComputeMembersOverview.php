<?php

namespace App\Actions\Admin;

use App\Models\User;
use App\Models\WatchlistItem;

class ComputeMembersOverview
{
    /**
     * `WatchlistItem` porte un global scope `owner` qui restreint toute requête aux films de
     * l'utilisateur connecté (voir WatchlistItem::booted). Une vue admin doit au contraire
     * embrasser tous les comptes : chaque requête ci-dessous désactive explicitement ce scope.
     */
    public function handle(): array
    {
        $members = User::query()
            ->withCount(['watchlistItems' => fn ($query) => $query->withoutGlobalScope('owner')])
            ->orderBy('created_at')
            ->get();

        $genreCounts = WatchlistItem::query()
            ->withoutGlobalScope('owner')
            ->pluck('genre')
            ->flatMap(fn ($genre) => array_map('trim', explode(',', (string) $genre)))
            ->filter()
            ->countBy()
            ->sortDesc();

        return [
            'members' => $members,
            'totalFilms' => $members->sum('watchlist_items_count'),
            'genreCounts' => $genreCounts,
            'topGenreCount' => $genreCounts->first(),
        ];
    }
}
