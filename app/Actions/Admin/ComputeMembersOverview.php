<?php

namespace App\Actions\Admin;

use App\Actions\Stats\ComputeCommunityStats;
use App\Enums\WatchStatus;
use App\Models\User;
use App\Models\WatchlistItem;
use App\Support\Movies\Genres;
use App\Support\StatsCache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ComputeMembersOverview
{
    /**
     * `WatchlistItem` porte un global scope `owner` qui restreint toute requête aux films de
     * l'utilisateur connecté (voir WatchlistItem::booted). Une vue admin doit au contraire
     * embrasser tous les comptes : chaque requête ci-dessous désactive explicitement ce scope.
     *
     * Seules les colonnes utiles aux statistiques sont chargées : les notes personnelles des
     * membres (`note`) ne sortent jamais de la base pour une vue admin.
     *
     * Les statistiques agrégées (sans détail par membre) viennent de ComputeCommunityStats,
     * partagée avec la page « Bilan » publique — voir cette classe pour le détail des calculs.
     * On lui passe `$items` déjà chargés pour éviter de les requêter une seconde fois.
     *
     * Le calcul qui parcourt TOUS les films (stats collectives + compteurs par membre) est mis en
     * cache, invalidé à chaque changement de la liste (voir StatsCache). La liste des membres et leur
     * dernière activité restent lues en direct : ce sont deux petites requêtes, et une « dernière
     * activité » périmée serait trompeuse.
     */
    public function handle(ComputeCommunityStats $communityStats): array
    {
        $members = User::query()
            ->realMembers()
            ->withCount(['watchlistItems' => fn ($query) => $query->withoutGlobalScope('owner')])
            ->orderBy('created_at')
            ->get();

        $aggregates = Cache::remember(
            StatsCache::MEMBERS_OVERVIEW,
            now()->addMinutes(StatsCache::TTL_MINUTES),
            fn () => $this->aggregate($communityStats),
        );

        // Dernière activité connue = la session la plus récente du compte (sessions en base).
        $lastActivity = DB::table('sessions')
            ->whereNotNull('user_id')
            ->selectRaw('user_id, MAX(last_activity) as last_activity')
            ->groupBy('user_id')
            ->pluck('last_activity', 'user_id');

        $memberStats = [];

        foreach ($members as $member) {
            $timestamp = $lastActivity->get($member->id);

            $memberStats[$member->id] = ($aggregates['perMember'][$member->id] ?? ['watched' => 0, 'toWatch' => 0, 'topGenres' => []])
                + ['lastActivity' => $timestamp ? Carbon::createFromTimestamp((int) $timestamp) : null];
        }

        return array_merge($aggregates['community'], [
            'members' => $members,
            'memberStats' => $memberStats,
            'adminCount' => $members->filter(fn (User $member) => $member->isAdmin())->count(),
        ]);
    }

    /**
     * Partie mise en cache : un seul chargement des films de tous les membres, dont on tire les stats
     * collectives et, par membre, vus / à voir / genres favoris (tableaux simples, faciles à sérialiser).
     *
     * @return array{community: array, perMember: array<int, array{watched: int, toWatch: int, topGenres: array<int, string>}>}
     */
    private function aggregate(ComputeCommunityStats $communityStats): array
    {
        $items = WatchlistItem::query()->adminSafe()->get();

        $perMember = [];

        foreach ($items->groupBy('user_id') as $userId => $own) {
            $perMember[$userId] = [
                'watched' => $own->whereIn('status', WatchStatus::seen())->count(),
                'toWatch' => $own->where('status', WatchStatus::ToWatch)->count(),
                'topGenres' => Genres::count($own)->take(3)->keys()->all(),
            ];
        }

        return ['community' => $communityStats->handle($items), 'perMember' => $perMember];
    }
}
