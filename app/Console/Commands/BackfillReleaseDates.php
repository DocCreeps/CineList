<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Services\TmdbClient;
use Illuminate\Console\Command;

/**
 * Renseigne `release_date` pour les fiches de films (table `movies`) ajoutées avant l'introduction
 * de cette colonne (migration 2026_09_23_130000). Une seule requête TMDB par film, quel que soit le
 * nombre de membres qui l'ont dans leur liste. Sans cette date, un film "à voir" ne peut pas être identifié
 * comme "pas encore sorti" (le champ `year` seul ne suffit pas : un film sorti en janvier de
 * l'année en cours doit compter comme déjà sorti) — voir App\Actions\Stats\ComputeCommunityStats.
 */
class BackfillReleaseDates extends Command
{
    protected $signature = 'watchlist:backfill-release-dates';

    protected $description = "Renseigne la date de sortie (TMDB) des films de la watchlist qui n'en ont pas encore";

    public function handle(TmdbClient $tmdb): int
    {
        $movies = Movie::whereNull('release_date')->get();

        if ($movies->isEmpty()) {
            $this->info('Rien à faire : tous les films ont déjà une date de sortie.');

            return self::SUCCESS;
        }

        $this->info("{$movies->count()} film(s) sans date de sortie à mettre à jour.");

        $updated = 0;
        $failed = 0;

        $this->withProgressBar($movies, function (Movie $movie) use ($tmdb, &$updated, &$failed): void {
            $details = $tmdb->find($movie->tmdb_id);

            if (! $details || empty($details['release_date'])) {
                $failed++;

                return;
            }

            $movie->update(['release_date' => $details['release_date']]);
            $updated++;
        });

        $this->newLine(2);
        $this->info("{$updated} film(s) mis à jour.");

        if ($failed > 0) {
            $this->warn("{$failed} film(s) n'ont pas pu être retrouvés sur TMDB (clé API absente, film supprimé, etc.).");
        }

        return self::SUCCESS;
    }
}
