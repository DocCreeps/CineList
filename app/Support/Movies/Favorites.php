<?php

namespace App\Support\Movies;

use App\Enums\WatchStatus;
use App\Models\WatchlistItem;
use Illuminate\Support\Collection;

/**
 * « Favoris » calculés sur un ensemble de films : réalisateur, studio, film préféré. Partagé par
 * la page Bilan (un utilisateur) et la vue d'ensemble admin (tous les membres).
 */
class Favorites
{
    /**
     * Nombre de films par studio, du plus fréquent au moins fréquent. Le champ `studio` liste toutes
     * les sociétés de production du film, séparées par des virgules : le film compte une fois pour
     * chacune d'elles.
     *
     * @param  Collection<int, WatchlistItem>  $items
     * @return Collection<string, int>
     */
    public static function studios(Collection $items): Collection
    {
        return $items->pluck('studio')
            ->flatMap(fn ($studio) => FieldList::split($studio))
            ->countBy()
            ->sortDesc();
    }

    /**
     * Nombre de films par réalisateur, du plus fréquent au moins fréquent.
     *
     * @param  Collection<int, WatchlistItem>  $items
     * @return Collection<string, int>
     */
    public static function directors(Collection $items): Collection
    {
        return $items->pluck('director')
            ->map(fn ($director) => trim((string) $director))
            ->filter()
            ->countBy()
            ->sortDesc();
    }

    /**
     * Films préférés d'un utilisateur, du mieux noté au moins bien noté (départage : note TMDB,
     * puis visionnage le plus récent). Vide tant qu'aucun film n'est noté.
     *
     * @param  Collection<int, WatchlistItem>  $items
     * @return Collection<int, WatchlistItem>
     */
    public static function films(Collection $items, int $limit = 3): Collection
    {
        return $items->whereNotNull('personal_rating')
            ->sortByDesc(fn (WatchlistItem $item) => [
                (int) $item->personal_rating,
                (float) $item->tmdb_rating,
                $item->watched_at?->timestamp ?? 0,
            ])
            ->take($limit)
            ->values();
    }

    /**
     * Film préféré d'un utilisateur : celui qu'il a le mieux noté (départage : note TMDB, puis
     * visionnage le plus récent). Null tant qu'aucun film n'est noté.
     *
     * @param  Collection<int, WatchlistItem>  $items
     */
    public static function film(Collection $items): ?WatchlistItem
    {
        return self::films($items, 1)->first();
    }

    /**
     * Classements des films préférés de l'ensemble des membres, sous trois angles (chaque
     * classement est vide quand aucun film ne s'y prête) :
     *  - `general` : le nombre de fois vu seul (`watch_count`, tous membres confondus, voir
     *    groupFilms()) — volontairement indépendant de la note ET du nombre de membres qui ont
     *    ajouté le film (`adds`), pour que « Film préféré » ne soit pas un simple doublon du
     *    classement « Le plus ajouté » ni du « Mieux noté » ;
     *  - `adds`    : les films ajoutés par le plus de membres, quel que soit leur statut ;
     *  - `rating`  : les films les mieux notés en moyenne, sans tenir compte des ajouts.
     *
     * Chaque classement contient au plus `$limit` films (3 par défaut : le n° 1 puis les deux suivants),
     * du meilleur au moins bon.
     *
     * `$items` doit contenir les films de tous les membres ET de tous les statuts (à voir, vu, à
     * revoir) : un ajout compte dès que le film est dans la liste d'un membre. Chaque film est
     * regroupé par identifiant TMDB, quel que soit le membre qui l'a ajouté ou noté.
     *
     * `$popularity` permet de remplacer la mesure par défaut du classement « adds » (nombre de
     * membres distincts) par une autre. `$addsMinimum` filtre le classement « adds » (mais pas
     * « general » ni « rating ») aux films dont cette mesure atteint ce seuil ; 0 par défaut ne
     * filtre rien (comportement inchangé).
     *
     * @param  Collection<int, WatchlistItem>  $items
     * @param  (callable(Collection<int, WatchlistItem>): int)|null  $popularity
     * @return array{general: Collection<int, array<string, mixed>>, adds: Collection<int, array<string, mixed>>, rating: Collection<int, array<string, mixed>>}
     */
    public static function filmsAcrossMembers(Collection $items, int $limit = 3, ?callable $popularity = null, int $addsMinimum = 0): array
    {
        $films = self::groupFilms($items, $popularity);

        return [
            'general' => $films
                ->sortByDesc(fn (array $film) => [$film['views'], $film['average'] ?? 0])
                ->take($limit)
                ->values(),
            'adds' => $films
                ->filter(fn (array $film) => $film['adds'] >= $addsMinimum)
                ->sortByDesc(fn (array $film) => [$film['adds'], $film['average'] ?? 0, $film['ratings']])
                ->take($limit)
                ->values(),
            // Sans note, un film ne peut pas prétendre au titre de « mieux noté ».
            'rating' => $films
                ->filter(fn (array $film) => $film['ratings'] > 0)
                ->sortByDesc(fn (array $film) => [$film['average'], $film['ratings'], $film['adds']])
                ->take($limit)
                ->values(),
        ];
    }

    /**
     * Films préférés d'un seul utilisateur (bilan personnel, ou détail d'un membre côté admin),
     * avec exactement la même formule de score « générale » que filmsAcrossMembers() (nombre de
     * vues seul, voir groupFilms()) : le nombre de vues a un sens même pour un seul utilisateur,
     * contrairement au nombre de membres (« adds », toujours 1 ici et donc ignoré, y compris par
     * le carrousel — voir favorite-films-carousel.blade.php).
     *
     * Contrairement au mode communauté, PAS de dédoublonnage ici : avec peu de films, le n° 1
     * « général » coïncide très souvent avec le mieux noté, et dédoublonner réduirait alors le
     * carrousel à une seule diapositive (plus de défilement auto ni de flèches/points, cf.
     * favorite-films-carousel.blade.php `@if ($slides->count() > 1)`). On préfère ici toujours
     * proposer les 2 diapositives dès qu'un film s'y prête, quitte à répéter le même n° 1 sous des
     * angles différents.
     *
     * `$items` doit contenir tous les films vus et à revoir d'un seul utilisateur.
     *
     * @param  Collection<int, WatchlistItem>  $items
     * @return array{general: Collection<int, array<string, mixed>>, adds: Collection<int, array<string, mixed>>, rating: Collection<int, array<string, mixed>>}
     */
    public static function filmsForUser(Collection $items, int $limit = 3): array
    {
        return self::filmsAcrossMembers($items, $limit);
    }

    /**
     * Évite d'afficher deux fois le même film n° 1 dans le carrousel des favoris :
     *  - si le n° 1 « général » est aussi en tête de TOUS les autres angles non vides, une seule
     *    diapositive suffit : la générale ;
     *  - si le n° 1 « général » coïncide avec au moins un autre angle (mais pas tous), on ne
     *    garde pas la diapositive générale (elle n'apporterait rien de plus).
     *
     * Générique sur le nombre d'angles fournis en plus de "general" (adds/rating, aussi bien en
     * mode communauté qu'en mode personnel — voir filmsForUser()). Seuls
     * les n° 1 sont comparés : une diapositive écartée l'est en entier (son classement devient
     * vide), une diapositive conservée garde son classement complet.
     *
     * @param  array<string, Collection<int, array<string, mixed>>>  $favorites  Doit contenir la clé "general", plus au moins un autre angle.
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    public static function withoutDuplicates(array $favorites): array
    {
        $topGeneral = $favorites['general']->first();
        $otherKeys = array_diff(array_keys($favorites), ['general']);

        $same = fn (?array $a, ?array $b) => $a !== null && $b !== null && $a['tmdb_id'] === $b['tmdb_id'];

        $nonEmptyOtherKeys = collect($otherKeys)->filter(fn ($key) => $favorites[$key]->isNotEmpty());
        $matchingKeys = collect($otherKeys)->filter(fn ($key) => $same($topGeneral, $favorites[$key]->first()));

        // Le n° 1 général coïncide avec TOUS les autres angles non vides : une seule diapositive suffit.
        if ($nonEmptyOtherKeys->isNotEmpty() && $matchingKeys->count() === $nonEmptyOtherKeys->count()) {
            $result = ['general' => $favorites['general']];
            foreach ($otherKeys as $key) {
                $result[$key] = collect();
            }

            return $result;
        }

        // Le n° 1 général coïncide avec au moins un autre angle : la diapositive générale n'apporte rien de plus.
        if ($matchingKeys->isNotEmpty()) {
            $favorites['general'] = collect();
        }

        return $favorites;
    }

    /**
     * Une ligne par film (identifiant TMDB), avec ses ajouts, son nombre de vues, ses notes et son
     * score général.
     *
     * Le score général (0 à 100) repose uniquement sur le nombre de fois vu (`watch_count`, tous
     * membres confondus), rapporté au film le plus vu. Volontairement indépendant de la note et de
     * `adds` (nombre de membres qui l'ont ajouté, quel que soit leur statut) : la note alimente
     * uniquement le classement « Mieux noté », `adds` uniquement « Le plus ajouté » — sinon
     * « Film préféré » ferait doublon avec l'un des deux (voir filmsAcrossMembers()).
     *
     * @param  Collection<int, WatchlistItem>  $items
     * @param  (callable(Collection<int, WatchlistItem>): int)|null  $popularity  Remplace la mesure
     *      par défaut du classement « adds » (nombre de membres distincts) — voir filmsAcrossMembers().
     * @return Collection<int, array<string, mixed>>
     */
    private static function groupFilms(Collection $items, ?callable $popularity = null): Collection
    {
        $films = $items->groupBy('tmdb_id')->map(function (Collection $group) use ($popularity) {
            $first = $group->first();
            $rated = $group->whereNotNull('personal_rating');
            $views = (int) $group->sum('watch_count');

            return [
                'tmdb_id' => (string) $first->tmdb_id,
                'title' => $first->title,
                'year' => $first->year,
                'poster_url' => $first->poster_url,
                // Nombre de membres distincts par défaut (un même membre ne peut pas « voter »
                // deux fois) ; remplaçable par $popularity. N'alimente QUE le classement « adds »
                // (« Le plus ajouté ») : voir filmsAcrossMembers().
                'adds' => $popularity ? $popularity($group) : $group->pluck('user_id')->unique()->count(),
                // Nombre de fois vu, tous membres du groupe confondus (en pratique un seul membre
                // pour filmsForUser) — alimente seul le score général (voir plus haut), pas le
                // classement « adds ».
                'views' => $views,
                // Où ce film a été vu pour la première fois (voir filmsForUser côté carrousel) :
                // n'a de sens que pour un groupe à un seul film (un seul utilisateur).
                'first_watched_source' => $first->first_watched_source?->value,
                'ratings' => $rated->count(),
                'average' => $rated->isNotEmpty() ? round((float) $rated->avg('personal_rating'), 1) : null,
                // Répartition des ajouts par statut, pour l'affichage (« 2 vus · 1 à voir »).
                'statuses' => [
                    'to_watch' => $group->where('status', WatchStatus::ToWatch)->count(),
                    'watched' => $group->where('status', WatchStatus::Watched)->count(),
                    'to_rewatch' => $group->where('status', WatchStatus::ToRewatch)->count(),
                ],
            ];
        });

        $maxViews = max(1, (int) $films->max('views'));

        return $films->map(function (array $film) use ($maxViews) {
            $film['score'] = (int) round(100 * $film['views'] / $maxViews);

            return $film;
        });
    }
}
