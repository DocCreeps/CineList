<?php

namespace App\Actions\Admin;

use App\Models\User;
use App\Models\WatchlistItem;
use App\Support\Movies\Favorites;
use App\Support\Movies\Genres;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ComputeMembersOverview
{
    /**
     * `WatchlistItem` porte un global scope `owner` qui restreint toute requête aux films de
     * l'utilisateur connecté (voir WatchlistItem::booted). Une vue admin doit au contraire
     * embrasser tous les comptes : chaque requête ci-dessous désactive explicitement ce scope.
     *
     * Seules les colonnes utiles aux statistiques sont chargées : les notes personnelles des
     * membres (`note`) ne sortent jamais de la base pour une vue admin.
     */
    public function handle(): array
    {
        $members = User::query()
            ->withCount(['watchlistItems' => fn ($query) => $query->withoutGlobalScope('owner')])
            ->orderBy('created_at')
            ->get();

        $items = WatchlistItem::query()
            ->withoutGlobalScope('owner')
            ->get(['user_id', 'tmdb_id', 'title', 'year', 'poster_url', 'status', 'genre', 'director', 'studio', 'personal_rating']);

        $genreCounts = Genres::count($items);

        // Réalisateur et studio préférés : calculés sur les films déjà vus, tous membres confondus.
        $watchedItems = $items->whereIn('status', ['watched', 'to_rewatch']);
        $directorCounts = Favorites::directors($watchedItems);
        $studioCounts = Favorites::studios($watchedItems);
        $itemsByUser = $items->groupBy('user_id');

        // Dernière activité connue = la session la plus récente du compte (sessions en base).
        $lastActivity = DB::table('sessions')
            ->whereNotNull('user_id')
            ->selectRaw('user_id, MAX(last_activity) as last_activity')
            ->groupBy('user_id')
            ->pluck('last_activity', 'user_id');

        $memberStats = [];

        foreach ($members as $member) {
            $own = $itemsByUser->get($member->id, collect());
            $timestamp = $lastActivity->get($member->id);

            $memberStats[$member->id] = [
                'watched' => $own->whereIn('status', ['watched', 'to_rewatch'])->count(),
                'toWatch' => $own->where('status', 'to_watch')->count(),
                'topGenres' => Genres::count($own)->take(3)->keys()->all(),
                'lastActivity' => $timestamp ? Carbon::createFromTimestamp((int) $timestamp) : null,
            ];
        }

        return [
            'members' => $members,
            'memberStats' => $memberStats,
            'adminCount' => $members->filter(fn (User $member) => $member->isAdmin())->count(),
            'totalFilms' => $members->sum('watchlist_items_count'),
            'watchedTotal' => $watchedItems->count(),
            'genreCounts' => $genreCounts,
            'topGenreCount' => $genreCounts->first(),
            'topDirector' => $directorCounts->keys()->first(),
            'topDirectorCount' => $directorCounts->first(),
            'topStudio' => $studioCounts->keys()->first(),
            'topStudioCount' => $studioCounts->first(),
            // Films préférés : calculés sur TOUS les statuts (à voir, vu, à revoir), car le nombre de
            // membres qui ont ajouté un film compte autant que sa note.
            'favoriteFilms' => Favorites::withoutDuplicates(Favorites::filmsAcrossMembers($items)),
        ];
    }
}
