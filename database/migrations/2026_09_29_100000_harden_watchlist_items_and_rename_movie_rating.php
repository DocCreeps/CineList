<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Durcit le schéma après la séparation `movies` / `watchlist_items` :
 *
 *  - `watchlist_items.user_id` devient obligatoire : un film sans propriétaire échapperait au scope
 *    `owner` et n'aurait aucun sens (la colonne n'était nullable que le temps d'attribuer les films
 *    antérieurs aux comptes, voir la commande `watchlist:assign-owner`) ;
 *  - index (user_id, status) : toutes les pages filtrent d'abord sur le membre, puis sur le statut ;
 *  - `movies.imdb_rating` devient `tmdb_rating` : la valeur est la note moyenne (`vote_average`) de
 *    TMDB, pas celle d'IMDb. Seul le nom change, les données sont conservées.
 *
 * Sauvegarder la base avant de migrer (SQLite : copier `database/database.sqlite`).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Refuse de rendre la colonne obligatoire tant que des films n'ont pas de propriétaire :
        // les supprimer ou en deviner un en silence ferait perdre ou mélanger des données.
        $orphans = DB::table('watchlist_items')->whereNull('user_id')->count();

        if ($orphans > 0) {
            throw new RuntimeException(
                "{$orphans} film(s) de watchlist_items n'ont pas de propriétaire (user_id NULL). "
                .'Attribuez-les avec `php artisan watchlist:assign-owner`, puis relancez la migration.'
            );
        }

        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->index(['user_id', 'status']);
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->renameColumn('imdb_rating', 'tmdb_rating');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->renameColumn('tmdb_rating', 'imdb_rating');
        });

        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->foreignId('user_id')->nullable()->change();
        });
    }
};
