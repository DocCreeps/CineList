<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\WatchlistItem;
use Illuminate\Console\Command;

/**
 * Supprime les fiches de la table `movies` que plus aucun membre ne référence. La suppression
 * d'un compte (ou d'un watchlist_item) ne touche pas à ces fiches, partagées entre tous les
 * membres : sans ce nettoyage, la table ne fait que croître. Planifié chaque semaine dans
 * routes/console.php ; exécutable à la main avec --dry-run pour compter avant de supprimer.
 */
class PurgeOrphanMovies extends Command
{
    protected $signature = 'movies:purge-orphans
        {--dry-run : Compte les fiches orphelines sans les supprimer}';

    protected $description = 'Supprime les fiches de films (movies) que plus aucun membre ne référence';

    public function handle(): int
    {
        // whereNotIn plutôt qu'un whereDoesntHave : pas de relation à maintenir sur Movie, et la
        // sous-requête exclut d'un seul coup tous les films encore référencés.
        $orphanIds = Movie::query()
            ->whereNotIn('id', WatchlistItem::query()->select('movie_id'))
            ->pluck('id');

        if ($orphanIds->isEmpty()) {
            $this->info('Aucune fiche orpheline : rien à faire.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("{$orphanIds->count()} fiche(s) orpheline(s) — aucune suppression (--dry-run).");

            return self::SUCCESS;
        }

        Movie::query()->whereIn('id', $orphanIds)->delete();

        $this->info("{$orphanIds->count()} fiche(s) orpheline(s) supprimée(s).");

        return self::SUCCESS;
    }
}
