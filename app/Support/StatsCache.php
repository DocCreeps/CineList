<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Clés et durée des statistiques mises en cache (bilan collectif et vue d'ensemble admin), et leur
 * invalidation commune : elles dépendent toutes des mêmes données (les films de tous les membres).
 */
class StatsCache
{
    public const COMMUNITY = 'stats.community.v1';

    public const MEMBERS_OVERVIEW = 'stats.members-overview.v1';

    public const TTL_MINUTES = 10;

    /**
     * Invalide tous les chiffres en cache ; le prochain affichage les recalcule.
     *
     * Sans effet quand l'action vient d'un compte démo : ses films n'entrent dans aucune de ces
     * statistiques (voir WatchlistItem::scopeAdminSafe), et chaque visiteur de la démo ne doit pas
     * pouvoir vider le cache de tout le monde.
     */
    public static function forget(): void
    {
        if (auth()->user()?->isDemo()) {
            return;
        }

        Cache::forget(self::COMMUNITY);
        Cache::forget(self::MEMBERS_OVERVIEW);
    }
}
