<?php

namespace App\Actions\Stats;

use App\Enums\WatchSource;
use App\Enums\WatchStatus;
use App\Models\WatchlistItem;
use App\Support\Movies\Favorites;
use App\Support\Movies\Genres;
use Illuminate\Support\Collection;
use App\Support\StatsCache;
use Illuminate\Support\Facades\Cache;

/**
 * Statistiques agrégées, tous membres confondus, sans aucun détail nominatif par membre (pas de
 * liste de comptes, pas de répartition individuelle). C'est ce qui rend cette action utilisable
 * aussi bien depuis la vue d'ensemble admin (App\Actions\Admin\ComputeMembersOverview, qui y
 * ajoute le détail des membres) que depuis la page « Bilan » publique, accessible à tout membre
 * connecté.
 *
 * `WatchlistItem` porte un global scope `owner` qui restreint toute requête aux films de
 * l'utilisateur connecté (voir WatchlistItem::booted) : le scope `adminSafe()` le désactive pour
 * embrasser tous les comptes, et ne charge que les colonnes autorisées (jamais la note privée).
 */
class ComputeCommunityStats
{
    /**
     * Invalide les stats en cache. Appelée automatiquement quand un film de la liste est créé,
     * modifié ou supprimé (voir WatchlistItem::booted), et à la main après une opération qui
     * contourne les événements Eloquent (mise à jour/suppression en masse, suppression d'un membre).
     * Le délai d'expiration reste le filet de sécurité pour tout le reste.
     */
    public static function forget(): void
    {
        StatsCache::forget();
    }

    /**
     * @param  Collection<int, WatchlistItem>|null  $items  Films de tous les membres, déjà chargés
     *      (colonnes utiles aux stats) — pour éviter une requête redondante quand l'appelant les a
     *      déjà en main (voir ComputeMembersOverview). Rechargés depuis la base si omis.
     *
     *      Sans `$items`, le résultat est mis en cache (il ne dépend pas du membre connecté : le scope
     *      `owner` est désactivé). Avec `$items`, l'appelant veut des chiffres calculés sur ses
     *      données du moment : aucun cache.
     */
    public function handle(?Collection $items = null): array
    {
        if ($items !== null) {
            return $this->compute($items);
        }

        // Mêmes chiffres pour tous les membres : un seul calcul sert chaque visite de « Bilan ».
        return Cache::remember(
            StatsCache::COMMUNITY,
            now()->addMinutes(StatsCache::TTL_MINUTES),
            fn () => $this->compute(WatchlistItem::query()->adminSafe()->get()),
        );
    }

    /** @param  Collection<int, WatchlistItem>  $items */
    private function compute(Collection $items): array
    {
        $genreCounts = Genres::count($items);

        // Réalisateur et studio préférés : calculés sur les films déjà vus, tous membres confondus.
        $watchedItems = $items->whereIn('status', WatchStatus::seen());
        $directorCounts = Favorites::directors($watchedItems);
        $studioCounts = Favorites::studios($watchedItems);

        // Vus au cinéma / en streaming, tous membres confondus : uniquement les films actuellement
        // "watched" (pas "to_rewatch"), comme ComputeWatchlistStats côté bilan personnel.
        $watchedOnly = $items->where('status', WatchStatus::Watched);

        // Films "les plus attendus" du bilan collectif : uniquement des films "à voir" pas encore
        // sortis (`release_date` dans le futur — les films sans date connue sont exclus, faute de
        // pouvoir dire s'ils sont réellement à venir), classés par nombre de membres qui les ont
        // ajoutés. Jamais affiché dans le bilan personnel.
        $upcoming = $items->where('status', WatchStatus::ToWatch)
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
            'cinemaCount' => $watchedOnly->where('source', WatchSource::Cinema)->count(),
            'streamingCount' => $watchedOnly->where('source', WatchSource::Streaming)->count(),
        ];
    }
}
