<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Applique la migration.
     */
    public function up(): void
    {
        Schema::table('watchlist_items', function (Blueprint $table) {
            // Nullable pour l'instant : les lignes existantes sont antérieures aux comptes et
            // n'ont pas encore de propriétaire. Voir la commande artisan `watchlist:assign-owner`
            // pour les attribuer.
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // La contrainte d'unicité d'origine portait sur l'ancienne colonne `imdb_id` et peut
        // encore porter ce nom après le renommage en `tmdb_id` (SQLite conserve le nom des index
        // lors d'un renommage de colonne). On la supprime par précaution sous les deux noms
        // possibles — `IF EXISTS` rend l'opération sans effet si le nom ne correspond à rien,
        // au lieu de faire échouer la migration.
        DB::statement('DROP INDEX IF EXISTS watchlist_items_imdb_id_unique');
        DB::statement('DROP INDEX IF EXISTS watchlist_items_tmdb_id_unique');

        // Remplacée par une contrainte par utilisateur, pour que deux utilisateurs différents
        // puissent chacun ajouter le même film.
        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->unique(['user_id', 'tmdb_id']);
        });
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'tmdb_id']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->unique('tmdb_id');
        });
    }
};
