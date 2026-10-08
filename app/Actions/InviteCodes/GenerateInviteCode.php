<?php

namespace App\Actions\InviteCodes;

use App\Models\InviteCode;
use App\Models\User;

class GenerateInviteCode
{
    /**
     * Génère un nouveau code d'invitation.
     *
     * @param  int|null  $expiresInDays  Nombre de jours avant expiration (illimité si null).
     * @param  int|null  $maxUses  Nombre d'inscriptions autorisées avec ce code (illimité si null, usage unique si 1).
     * @param  string|null  $sentTo  Adresse e-mail à laquelle le code sera envoyé (facultatif).
     * @param  User|null  $creator  Administrateur à l'origine du code (facultatif, pour la traçabilité).
     */
    public function handle(?int $expiresInDays = null, ?int $maxUses = 1, ?string $sentTo = null, ?User $creator = null): InviteCode
    {
        return InviteCode::create([
            'code' => $this->generateUniqueCode(),
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
            'max_uses' => $maxUses,
            'sent_to' => $sentTo,
            'created_by' => $creator?->id,
        ]);
    }

    /** Lettres et chiffres sans I, L, O, 0, 1 (confusions à la lecture) : 31 symboles. */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * Code de la forme XXXX-XXXX-XXXX : 12 symboles tirés au hasard cryptographique (random_int),
     * soit environ 59 bits. Les anciens codes à 8 caractères (≈ 41 bits) restent valables.
     */
    private function generateUniqueCode(): string
    {
        do {
            $groups = [];

            for ($g = 0; $g < 3; $g++) {
                $group = '';

                for ($i = 0; $i < 4; $i++) {
                    $group .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
                }

                $groups[] = $group;
            }

            $code = implode('-', $groups);
        } while (InviteCode::query()->where('code', $code)->exists());

        return $code;
    }
}
