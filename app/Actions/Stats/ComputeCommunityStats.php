<?php

namespace App\Actions\Stats;

use App\Models\WatchlistItem;
use App\Support\Movies\Favorites;
use App\Support\Movies\Genres;
use Illuminate\Support\Collection;

/**
 * Statistiques agrégées, tous membres confondus, sans aucun détail nominatif par membre (pas de
 * liste de comptes, pas de répartition individuelle). C'est ce qui rend cette action utilisable
 * aussi bien depuis la vue d'ensemble admin (App\Actions\Admin\ComputeMembersOverview, qui y
 * ajoute le détail des membres) que depuis la page « Bilan » publique, accessible à tout membre
 * connecté.
 *
 * `WatchlistItem` porte un global scope `owner` qui restreint toute requête aux films de
 * l'utilisateur connecté (voir WatchlistItem::booted) : on le désactive explicitement ici pour
 * embrasser tous les comptes.
 */
class ComputeCommunityStats
{
    /**
     * @param  Collection<int, WatchlistItem>|null  $items  Films de tous les membres, déjà chargés
     *      (colonnes utiles aux stats) — pour éviter une requête redondante quand l'appelant les a
     *      déjà en main (voir ComputeMembersOverview). Rechargés depuis la base si omis.
     */
    public function handle(?Collection $items = null): array
    {
        $items ??= WatchlistItem::query()
            ->withoutGlobalScope('owner')
            ->get(WatchlistItem::ADMIN_SAFE_COLUMNS);

        $genreCounts = Genres::count($items);

        // Réalisateur et studio préférés : calculés sur les films déjà vus, tous membres confondus.
        $watchedItems = $items->whereIn('status', ['watched', 'to_rewatch']);
        $directorCounts = Favorites::directors($watchedItems);
        $studioCounts = Favorites::studios($watchedItems);

        $toRewatchItems = $items->where('status', 'to_rewatch');

        // Films "les plus attendus" du bilan collectif : uniquement des films "à voir" pas encore
        // sortis (`release_date` dans le futur — les films sans date connue sont exclus, faute de
        // pouvoir dire s'ils sont réellement à venir), classés par nombre de membres qui les ont
        // ajoutés. Jamais affiché dans le bilan personnel.
        $upcoming = $items->where('status', 'to_watch')
            ->filter(fn (WatchlistItem $item) => $item->release_date?->isFuture());
        $mostAnticipated = Favorites::filmsAcrossMembers($upcoming, 3)['adds'];

        $favoriteFilms = Favorites::filmsAcrossMembers($items, 3);

        return [
            'totalFilms' => $items->count(),
            'watchedTotal' => $watchedItems->count(),
            'genreCounts' => $genreCounts,
            'topGenreCount' => $genreCounts->first(),
            // Podiums : les 3 premiers (nom => nombre de films vus), du plus au moins regardé.
            'topDirectors' => $directorCounts->take(3),
            'topStudios' => $studioCounts->take(3),
            // Films préférés : calculés sur TOUS les statuts (à voir, vu, à revoir), car le nombre de
            // membres qui ont ajouté un film compte autant que sa note. Trois films par angle.
            // Dédoublonnage restreint à general/adds/rating : l'angle "views" (non affiché côté
            // communauté) ne doit pas influer sur quelle diapositive est gardée.
            'favoriteFilms' => Favorites::withoutDuplicates([
                'general' => $favoriteFilms['general'],
                'adds' => $favoriteFilms['adds'],
                'rating' => $favoriteFilms['rating'],
            ]),
            'mostAnticipated' => $mostAnticipated,
            'toRewatchTotal' => $toRewatchItems->count(),
            // Où ces films ont été vus pour la 1ère fois, tous membres confondus (champ distinct de `source`).
            'toRewatchFirstSeenCounts' => [
                'cinema' => $toRewatchItems->where('first_watched_source', 'cinema')->count(),
                'streaming' => $toRewatchItems->where('first_watched_source', 'streaming')->count(),
            ],
        ];
    }
}
