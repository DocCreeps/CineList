<?php

return [

    /*
    | Interrupteur général des liens démo (/demo/{token}, gérés dans Admin → Liens démo).
    | DEMO_ENABLED=false refuse tous les liens d'un coup, sans toucher à la base.
    */
    'enabled' => env('DEMO_ENABLED', false),

    /*
    | Chaque visite d'un lien démo crée un compte fictif jetable (un « bac à sable »), copie du
    | compte modèle créé par `php artisan demo:create`. Le visiteur peut tout faire dessus
    | (ajouter, noter, supprimer…) sans aucun effet sur les vrais comptes. Le bac à sable est
    | supprimé automatiquement après cette durée (commande `demo:purge`, planifiée toutes les
    | heures, et purge opportuniste à chaque nouvelle visite d'un lien).
    */
    'sandbox_ttl_hours' => (int) env('DEMO_SANDBOX_TTL_HOURS', 24),

    /*
    | Nombre maximal de bacs à sable simultanés : au-delà, le lien répond 503 le temps qu'ils
    | expirent. Protège la base contre un lien partagé massivement ou un robot.
    */
    'max_sandboxes' => (int) env('DEMO_MAX_SANDBOXES', 200),
];
