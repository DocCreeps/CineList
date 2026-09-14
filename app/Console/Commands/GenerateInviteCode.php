<?php

namespace App\Console\Commands;

use App\Actions\InviteCodes\GenerateInviteCode as GenerateInviteCodeAction;
use Illuminate\Console\Command;

class GenerateInviteCode extends Command
{
    protected $signature = 'invite:generate {--expires-in-days= : Nombre de jours avant expiration (illimité si omis)}';

    protected $description = "Génère un code d'invitation à usage unique pour l'inscription";

    public function handle(GenerateInviteCodeAction $action): int
    {
        $expiresInDays = $this->option('expires-in-days');

        $invite = $action->handle($expiresInDays ? (int) $expiresInDays : null);

        $this->info("Code d'invitation généré : {$invite->code}");

        if ($invite->expires_at) {
            $this->line('Expire le : '.$invite->expires_at->format('d/m/Y'));
        }

        $this->line('Astuce : un administrateur peut aussi générer et envoyer un code par e-mail depuis /admin/invitations.');

        return self::SUCCESS;
    }
}
