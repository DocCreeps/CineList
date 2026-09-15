<?php

namespace App\Console\Commands;

use App\Actions\InviteCodes\GenerateInviteCode as GenerateInviteCodeAction;
use Illuminate\Console\Command;

class GenerateInviteCode extends Command
{
    protected $signature = 'invite:generate
        {--expires-in-days= : Nombre de jours avant expiration (illimité si omis)}
        {--uses=1 : Nombre d\'inscriptions autorisées avec ce code ("0" ou "illimite" pour un usage illimité)}';

    protected $description = "Génère un code d'invitation pour l'inscription";

    public function handle(GenerateInviteCodeAction $action): int
    {
        $expiresInDays = $this->option('expires-in-days');
        $usesOption = strtolower((string) $this->option('uses'));
        $maxUses = in_array($usesOption, ['0', 'illimite', 'illimité', 'unlimited'], true) ? null : (int) $usesOption;

        $invite = $action->handle(
            expiresInDays: $expiresInDays ? (int) $expiresInDays : null,
            maxUses: $maxUses,
        );

        $this->info("Code d'invitation généré : {$invite->code}");
        $this->line('Utilisations autorisées : '.($invite->isUnlimited() ? 'illimité' : $invite->max_uses));

        if ($invite->expires_at) {
            $this->line('Expire le : '.$invite->expires_at->format('d/m/Y'));
        }

        $this->line('Astuce : un administrateur peut aussi générer et envoyer un code par e-mail depuis /admin/invitations.');

        return self::SUCCESS;
    }
}
