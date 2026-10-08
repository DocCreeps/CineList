<?php

namespace App\Actions\Watchlist;

use App\Actions\Stats\ComputeCommunityStats;
use App\Enums\WatchStatus;
use App\Models\WatchlistItem;
use Illuminate\Support\Facades\DB;

/**
 * Changement de statut groupé (barre d'actions du tableau de bord) : applique à chaque film
 * sélectionné la même logique que UpdateWatchlistItemStatus (dates de visionnage, compteur de vues…).
 *
 * L'ensemble tourne dans une transaction : si un film échoue en cours de route, aucun des autres
 * n'est modifié, au lieu de laisser la sélection à moitié traitée.
 */
class BulkUpdateWatchlistStatus
{
    public function __construct(private UpdateWatchlistItemStatus $updateStatus) {}

    /**
     * @param  array<int, int|string>  $ids  Identifiants cochés (viennent du navigateur : jamais fiables).
     * @return int  Nombre de films effectivement traités.
     */
    public function handle(array $ids, string $status): int
    {
        abort_unless(WatchStatus::tryFrom($status) !== null, 422);

        // Une seule requête, restreinte aux films du membre par le scope `owner` : un identifiant
        // inconnu, supprimé entre-temps ou appartenant à un autre membre est simplement ignoré.
        $done = DB::transaction(function () use ($ids, $status): int {
            $items = WatchlistItem::query()
                ->whereIn('id', array_map('intval', $ids))
                ->get();

            foreach ($items as $item) {
                $this->updateStatus->handle($item, $status);
            }

            return $items->count();
        });

        ComputeCommunityStats::forget();

        return $done;
    }
}
