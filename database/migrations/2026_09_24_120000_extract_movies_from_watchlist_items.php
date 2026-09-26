<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sépare les métadonnées d'un film (identiques pour tous les membres) de sa présence dans la liste
 * d'un membre :
 *
 *  - `movies` : une ligne par `tmdb_id` (titre, affiche, genre, réalisateur, studio, synopsis…) ;
 *  - `watchlist_items` : une ligne par membre et par film (`user_id`, `movie_id`, statut, source,
 *    priorité, notes, dates et nombre de visionnages).
 *
 * Les films déjà présents chez plusieurs membres sont fusionnés en une seule ligne de `movies` (la
 * mise à jour la plus récente l'emporte, sans jamais remplacer une valeur par du vide). Aucune
 * donnée personnelle n'est modifiée : les lignes de `watchlist_items` gardent leur `id`.
 *
 * Sauvegarder la base avant de migrer : la suppression des colonnes déplacées est destructive
 * (down() les restaure depuis `movies`).
 */
return new class extends Migration
{
    /** Colonnes qui quittent `watchlist_items` pour `movies`. */
    private const MOVIE_COLUMNS = [
        'tmdb_id', 'title', 'year', 'poster_url', 'type', 'genre', 'director', 'actors', 'studio',
        'runtime', 'imdb_rating', 'plot', 'release_date',
    ];

    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('tmdb_id')->unique();
            $table->string('title');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('poster_url', 2048)->nullable();
            $table->string('type', 32)->default('movie');
            $table->string('genre')->nullable();
            $table->string('director')->nullable();
            $table->string('actors')->nullable();
            $table->string('studio')->nullable();
            $table->string('runtime', 32)->nullable();
            $table->decimal('imdb_rating', 3, 1)->nullable();
            $table->text('plot')->nullable();
            $table->date('release_date')->nullable();
            $table->timestamps();
        });

        // Nullable le temps de la copie ; rendue obligatoire plus bas.
        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->foreignId('movie_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
        });

        $this->copyMoviesOut();

        // L'unicité (user_id, tmdb_id) porte sur une colonne qui disparaît : elle doit être retirée
        // avant, puis remplacée par (user_id, movie_id).
        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'tmdb_id']);
        });

        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->dropColumn(self::MOVIE_COLUMNS);
        });

        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->foreignId('movie_id')->nullable(false)->change();
            $table->unique(['user_id', 'movie_id']);
        });
    }

    public function down(): void
    {
        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'movie_id']);
        });

        // `title` était obligatoire à l'origine ; redevient nullable ici car les lignes existantes
        // ne peuvent pas être remplies avant d'être copiées ci-dessous.
        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->string('tmdb_id')->nullable();
            $table->string('title')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('poster_url', 2048)->nullable();
            $table->string('type', 32)->default('movie');
            $table->string('genre')->nullable();
            $table->string('director')->nullable();
            $table->string('actors')->nullable();
            $table->string('studio')->nullable();
            $table->string('runtime', 32)->nullable();
            $table->decimal('imdb_rating', 3, 1)->nullable();
            $table->text('plot')->nullable();
            $table->date('release_date')->nullable();
        });

        DB::table('movies')->orderBy('id')->each(function ($movie) {
            DB::table('watchlist_items')->where('movie_id', $movie->id)->update([
                'tmdb_id' => $movie->tmdb_id,
                'title' => $movie->title,
                'year' => $movie->year,
                'poster_url' => $movie->poster_url,
                'type' => $movie->type,
                'genre' => $movie->genre,
                'director' => $movie->director,
                'actors' => $movie->actors,
                'studio' => $movie->studio,
                'runtime' => $movie->runtime,
                'imdb_rating' => $movie->imdb_rating,
                'plot' => $movie->plot,
                'release_date' => $movie->release_date,
            ]);
        });

        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('movie_id');
        });

        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->unique(['user_id', 'tmdb_id']);
        });

        Schema::drop('movies');
    }

    /**
     * Crée une ligne `movies` par `tmdb_id` distinct et rattache chaque item à la sienne. Les items
     * sont lus du moins récemment au plus récemment modifié : pour un film présent chez plusieurs
     * membres, la valeur la plus récente gagne, mais une valeur vide n'écrase jamais une valeur connue.
     */
    private function copyMoviesOut(): void
    {
        $movies = [];

        DB::table('watchlist_items')->orderBy('updated_at')->orderBy('id')->each(function ($row) use (&$movies) {
            $tmdbId = (string) $row->tmdb_id;
            $merged = $movies[$tmdbId] ?? [];

            foreach (self::MOVIE_COLUMNS as $column) {
                if ($row->{$column} !== null && $row->{$column} !== '') {
                    $merged[$column] = $row->{$column};
                }
            }

            $movies[$tmdbId] = $merged;
        });

        $now = now();

        foreach ($movies as $tmdbId => $attributes) {
            $movieId = DB::table('movies')->insertGetId([
                ...$attributes,
                'tmdb_id' => (string) $tmdbId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('watchlist_items')->where('tmdb_id', (string) $tmdbId)->update(['movie_id' => $movieId]);
        }
    }
};
