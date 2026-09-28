<?php

namespace App\Services;

use App\Services\Tmdb\MovieDetails;
use App\Services\Tmdb\MovieSearch;
use App\Services\Tmdb\ReleaseCalendar;
use Illuminate\Support\Carbon;

/**
 * Point d'entrée unique vers TMDB pour le reste de l'application (composants Livewire, actions,
 * commandes artisan) : la signature publique de cette classe ne change pas, seule son
 * implémentation est désormais déléguée à des collaborateurs spécialisés sous App\Services\Tmdb :
 *  - MovieSearch      : recherche par titre, réalisateur, acteur ou studio ;
 *  - ReleaseCalendar  : sorties en salles françaises (page « À venir », date de sortie d'un film) ;
 *  - MovieDetails     : fiche complète, casting, films similaires, saga, plateformes de streaming.
 *  - TmdbConnector    : HTTP/authentification partagés par les trois collaborateurs ci-dessus.
 *
 * Objectif du découpage : TmdbClient regroupait ces quatre responsabilités dans un seul fichier de
 * 800+ lignes. Chacune reste testable et lisible séparément, sans rien changer pour les appelants.
 */
class TmdbClient
{
    public function __construct(
        private MovieSearch $search,
        private ReleaseCalendar $releases,
        private MovieDetails $details,
    ) {}

    /**
     * @return array{results: array<int, array<string, mixed>>, error: ?string}
     */
    public function searchFilms(string $query, string $mode = 'title', ?int $minYear = null): array
    {
        return $this->search->search($query, $mode, $minYear);
    }

    /**
     * @return array{results: array<int, array<string, mixed>>, error: ?string}
     */
    public function releasesBetween(Carbon $from, Carbon $to, bool $newestFirst = false): array
    {
        return $this->releases->between($from, $to, $newestFirst);
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<string, array{release_date: ?string, confirmed: bool}>
     */
    public function cinemaReleaseDates(array $ids): array
    {
        return $this->releases->forMovies($ids);
    }

    public function find(string $tmdbId): ?array
    {
        return $this->details->find($tmdbId);
    }

    /**
     * @return array{cast: array<int, array{name: string, character: ?string, photo_url: ?string}>, crew: array<string, string>, total: int}
     */
    public function credits(string $tmdbId): array
    {
        return $this->details->credits($tmdbId);
    }

    /**
     * @return array<int, array{tmdb_id: string, title: string, year: ?int, poster_url: ?string}>
     */
    public function similarFilms(string $tmdbId): array
    {
        return $this->details->similarFilms($tmdbId);
    }

    /**
     * @return array<int, array{tmdb_id: string, title: string, release_date: ?string}>
     */
    public function collectionFilms(int $collectionId): array
    {
        return $this->details->collectionFilms($collectionId);
    }

    /**
     * @return array{link: ?string, flatrate: array<int, array{name: string, logo_url: ?string}>, rent: array, buy: array}
     */
    public function watchProviders(string $tmdbId): array
    {
        return $this->details->watchProviders($tmdbId);
    }
}
