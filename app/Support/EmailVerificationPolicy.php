<?php

namespace App\Support;

/**
 * Décide si l'application exige un e-mail vérifié (voir config/auth.php, `require_verified_email`).
 * Isolée dans une classe pour pouvoir être testée sans manipuler les variables d'environnement.
 */
class EmailVerificationPolicy
{
    /** Pilotes qui n'envoient aucun vrai e-mail : le lien de vérification n'arriverait jamais. */
    private const FAKE_MAILERS = ['log', 'array'];

    /**
     * @param  mixed  $explicit  Valeur de REQUIRE_EMAIL_VERIFICATION : true/false force le choix ;
     *                           absente, null ou vide (`REQUIRE_EMAIL_VERIFICATION=` dans le .env) = automatique.
     */
    public static function required(mixed $explicit, string $environment, string $mailer): bool
    {
        if ($explicit !== null && $explicit !== '') {
            return filter_var($explicit, FILTER_VALIDATE_BOOLEAN);
        }

        return $environment === 'production' && ! in_array($mailer, self::FAKE_MAILERS, true);
    }
}
