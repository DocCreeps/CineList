<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('watchlist_items', function (Blueprint $table) {
            // Nullable for now: existing rows predate accounts and have no owner yet.
            // See the `watchlist:assign-owner` artisan command to attribute them.
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // The original unique constraint was created on the old `imdb_id` column and may still
        // carry that name after the later rename to `tmdb_id` (SQLite keeps index names across a
        // column rename). Drop it defensively under both possible names — `IF EXISTS` makes this
        // a no-op if a name doesn't match anything, instead of failing the migration.
        DB::statement('DROP INDEX IF EXISTS watchlist_items_imdb_id_unique');
        DB::statement('DROP INDEX IF EXISTS watchlist_items_tmdb_id_unique');

        // Replaced by a per-user constraint, so two different users can each add the same film.
        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->unique(['user_id', 'tmdb_id']);
        });
    }

    /**
     * Reverse the migrations.
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
