<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Enregistrement des services de l'application.
     */
    public function register(): void
    {
        //
    }

    /**
     * Amorçage des services de l'application.
     */
    public function boot(): void
    {
        // Livewire ne rejoue pas, sur ses requêtes de mise à jour (/livewire/update), les middlewares
        // de la route de la page : seuls ceux de sa liste « persistante » le sont. Sans ces deux
        // ajouts, `admin` et `password.confirm` ne protégeaient que le chargement initial de la page :
        // un compte rétrogradé, ou dont la confirmation de mot de passe a expiré, pouvait continuer à
        // appeler les actions d'une page admin ou de /parametres déjà ouverte (ex. deleteMember).
        Livewire::addPersistentMiddleware([
            EnsureUserIsAdmin::class,
            RequirePassword::class,
        ]);

        // Politique de mot de passe appliquée partout où Password::defaults() est utilisé
        // (formulaires d'inscription et de réinitialisation) : 12 caractères minimum,
        // majuscule + minuscule, au moins un chiffre, au moins un caractère spécial.
        Password::defaults(fn () => Password::min(12)
            ->mixedCase()
            ->letters()
            ->numbers()
            ->symbols());

        // Détecte les en-têtes envoyés par Cloudflare / Localtunnel / Ngrok — uniquement en
        // environnement local et pour un hôte explicitement autorisé (TUNNEL_HOSTS dans .env).
        // Sans ces deux garde-fous, un en-tête X-Forwarded-Host forgé par n'importe quel client
        // permettrait de rediriger les liens signés (réinitialisation de mot de passe,
        // vérification d'e-mail) vers un domaine contrôlé par un attaquant.
        if (app()->environment('local') && $forwardedHost = request()->header('x-forwarded-host')) {
            // Récupère uniquement le premier hôte si une liste séparée par des virgules est envoyée
            $host = trim(explode(',', $forwardedHost)[0]);

            $allowedHosts = array_filter(array_map('trim', explode(',', (string) config('app.tunnel_hosts'))));

            if ($host !== '' && in_array($host, $allowedHosts, true)) {
                $proto = request()->header('x-forwarded-proto', 'https');

                URL::forceRootUrl("{$proto}://{$host}");
                URL::forceScheme('https');
            }
        }
    }
}
