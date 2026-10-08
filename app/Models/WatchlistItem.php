<?php

namespace App\Models;

use App\Actions\Stats\ComputeCommunityStats;
use App\Enums\WatchSource;
use App\Enums\WatchStatus;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Un film dans la liste d'un membre : ses données personnelles (statut, source, priorité, notes,
 * dates et nombre de visionnages). Les métadonnées du film (titre, affiche, genre, réalisateur…)
 * vivent dans la table partagée `movies` — voir Movie.
 *
 * Pour que le reste de l'application n'ait pas à changer, ces métadonnées restent lisibles
 * directement sur l'item (`$item->title`, `$item->genre`…, voir MOVIE_ATTRIBUTES) : elles sont
 * lues sur la relation `movie`, toujours chargée avec l'item (`$with`). En revanche, en SQL,
 * filtrer ou trier sur ces champs passe par `whereMovie()` / `orderByMovie()`.
 */
class WatchlistItem extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $with = ['movie'];

    protected $fillable = ['movie_id', 'status', 'source', 'first_watched_source', 'priority', 'note', 'personal_rating', 'watched_at', 'watch_count'];

    /**
     * Champs du film lisibles directement sur l'item. Lecture seule : pour les modifier, passer par
     * `$item->movie` (voir aussi Movie::syncFromTmdb()).
     *
     * @var array<int, string>
     */
    public const MOVIE_ATTRIBUTES = [
        'tmdb_id', 'title', 'year', 'poster_url', 'type', 'genre', 'director', 'actors', 'studio',
        'runtime', 'tmdb_rating', 'plot', 'release_date',
    ];

    /**
     * Colonnes de `watchlist_items` qu'un contexte admin (vue d'ensemble, détail d'un membre,
     * statistiques de communauté) a le droit de charger en désactivant le scope `owner`. `note`
     * (texte libre privé) en est volontairement absente : un admin voit ce que fait un membre, pas
     * ses annotations personnelles. Source unique de vérité — les actions admin passent par le
     * scope `adminSafe()` plutôt que de retaper leur propre liste, pour qu'un futur champ sensible
     * ne soit exclu qu'à un seul endroit.
     */
    public const ADMIN_SAFE_COLUMNS = [
        'id', 'user_id', 'movie_id', 'status', 'source', 'first_watched_source', 'priority',
        'personal_rating', 'watched_at', 'watch_count', 'created_at',
    ];

    /** Colonnes de `movies` chargées avec les précédentes en contexte admin (`plot` et `actors` en sont exclus, comme avant). */
    public const ADMIN_SAFE_MOVIE_COLUMNS = [
        'id', 'tmdb_id', 'title', 'year', 'poster_url', 'genre', 'director', 'studio', 'runtime',
        'tmdb_rating', 'release_date',
    ];

    protected function casts(): array
    {
        return [
            'watched_at' => 'datetime',
            'watch_count' => 'integer',
            // Enums : `$item->status` est un cas WatchStatus (->value pour la chaîne), jamais une chaîne brute.
            'status' => WatchStatus::class,
            'source' => WatchSource::class,
            'first_watched_source' => WatchSource::class,
        ];
    }

    /**
     * Un film déjà vu au moins une fois (watched_at renseigné, qu'il soit actuellement "vu" ou
     * "à revoir") ne peut plus repasser au statut "à voir" — voir UpdateWatchlistItemStatus.
     */
    public function alreadyWatched(): bool
    {
        return $this->watched_at !== null;
    }

    /**
     * Le compteur "nombre de fois vu" (watch_count) reste corrigeable à la main tant qu'aucun
     * vrai revisionnage n'a eu lieu. Il se verrouille dès que le film est actuellement "à revoir",
     * ou dès qu'un cycle vu → à revoir → vu a déjà eu lieu (watch_count > 1, incrémenté
     * automatiquement — voir UpdateWatchlistItemStatus) : au-delà, seul cet incrément automatique
     * fait foi.
     */
    public function watchCountLocked(): bool
    {
        return $this->status === WatchStatus::ToRewatch || $this->watch_count > 1;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Movie, $this> */
    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * Les champs du film (MOVIE_ATTRIBUTES) sont servis par la relation `movie`, tout le reste par
     * Eloquent. Passer par getAttribute() (et non par des accesseurs) fait aussi marcher
     * `pluck('genre')`, `groupBy('tmdb_id')` ou `where('title', …)` sur une collection d'items.
     */
    public function getAttribute($key)
    {
        if (is_string($key) && in_array($key, self::MOVIE_ATTRIBUTES, true)) {
            return $this->movie?->getAttribute($key);
        }

        return parent::getAttribute($key);
    }

    /**
     * Contexte admin : ignore le scope `owner` (tous les membres) et ne charge que les colonnes
     * listées dans ADMIN_SAFE_COLUMNS / ADMIN_SAFE_MOVIE_COLUMNS — jamais la note privée.
     */
    public function scopeAdminSafe(Builder $query): void
    {
        // Les films des comptes fictifs de la démo ne comptent ni dans le bilan communautaire ni dans l'admin.
        $query->withoutGlobalScope('owner')
            ->whereDoesntHave('user', fn (Builder $user) => $user->where('is_demo', true))
            ->select(self::ADMIN_SAFE_COLUMNS)
            ->with('movie:'.implode(',', self::ADMIN_SAFE_MOVIE_COLUMNS));
    }

    /**
     * Restreint aux items dont le film vérifie les conditions données (ex. genre, réalisateur, année).
     *
     * @param  Closure(Builder<Movie>): mixed  $constraints
     */
    public function scopeWhereMovie(Builder $query, Closure $constraints): void
    {
        $query->whereHas('movie', $constraints);
    }

    /** Restreint aux items du film TMDB donné. */
    public function scopeWhereTmdbId(Builder $query, string $tmdbId): void
    {
        $query->whereHas('movie', fn (Builder $movie) => $movie->where('tmdb_id', $tmdbId));
    }

    /** Trie sur une colonne du film (sous-requête corrélée : pas de jointure, donc pas d'ambiguïté de colonnes). */
    public function scopeOrderByMovie(Builder $query, string $column, string $direction = 'asc'): void
    {
        $query->orderBy(
            Movie::query()->select($column)->whereColumn('movies.id', 'watchlist_items.movie_id')->limit(1),
            $direction,
        );
    }

    /**
     * Statut, dans la liste du membre connecté, de chacun des films TMDB donnés — pour griser les
     * films déjà ajoutés sur les pages de recherche et « À venir ».
     *
     * @param  iterable<int, string|int>  $tmdbIds
     * @return Collection<string, string>  tmdb_id => statut (seuls les films présents dans la liste)
     */
    public static function statusesByTmdbId(iterable $tmdbIds): Collection
    {
        $ids = collect($tmdbIds)->map(fn ($id) => (string) $id)->unique()->values()->all();

        if ($ids === []) {
            return collect();
        }

        return static::query()
            ->whereMovie(fn (Builder $movie) => $movie->whereIn('tmdb_id', $ids))
            ->get()
            ->mapWithKeys(fn (self $item) => [$item->tmdb_id => $item->status->value]);
    }

    /**
     * Chaque film appartient à un seul utilisateur. Un scope global limite automatiquement
     * toutes les requêtes existantes — recherche, tableau de bord, statistiques, accueil — aux
     * films de l'utilisateur connecté, sans avoir à les modifier une par une. `creating`
     * estampille les nouvelles lignes avec l'utilisateur courant.
     *
     * Sans utilisateur connecté (commande artisan, job de file, test), le scope est FERMÉ : la requête
     * ne renvoie rien. Un oubli d'authentification ne doit jamais exposer les films de tous les
     * membres. Le code qui a vraiment besoin de tout voir le demande explicitement :
     * `withoutGlobalScope('owner')` ou `adminSafe()`.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if (auth()->check()) {
                $query->where('watchlist_items.user_id', auth()->id());
            } else {
                $query->whereRaw('1 = 0');
            }
        });

        static::creating(function (self $item) {
            $item->user_id ??= auth()->id();
        });

        // Les stats communautaires sont mises en cache : tout changement de la liste les invalide.
        static::saved(fn () => ComputeCommunityStats::forget());
        static::deleted(fn () => ComputeCommunityStats::forget());
    }
}
