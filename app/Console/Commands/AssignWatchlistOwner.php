<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WatchlistItem;
use Illuminate\Console\Command;

class AssignWatchlistOwner extends Command
{
    protected $signature = 'watchlist:assign-owner {user : ID ou e-mail de l\'utilisateur}';

    protected $description = "Attribue à un utilisateur tous les films sans propriétaire (ajoutés avant la mise en place des comptes)";

    public function handle(): int
    {
        $identifier = $this->argument('user');

        $user = is_numeric($identifier)
            ? User::find($identifier)
            : User::where('email', $identifier)->first();

        if (! $user) {
            $this->error("Utilisateur introuvable : {$identifier}");

            return self::FAILURE;
        }

        // Sans le scope global : les lignes orphelines n'ont pas de propriétaire, et ce scope
        // ne filtre de toute façon que par l'utilisateur courant — inutile en contexte console.
        $count = WatchlistItem::withoutGlobalScope('owner')->whereNull('user_id')->count();

        if ($count === 0) {
            $this->info('Aucun film sans propriétaire à attribuer.');

            return self::SUCCESS;
        }

        WatchlistItem::withoutGlobalScope('owner')->whereNull('user_id')->update(['user_id' => $user->id]);

        $this->info("{$count} film(s) attribué(s) à {$user->name} ({$user->email}).");

        return self::SUCCESS;
    }
}
