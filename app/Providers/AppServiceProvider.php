<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Politique de mot de passe appliquée partout où Password::defaults() est utilisé
        // (formulaires d'inscription et de réinitialisation) : 12 caractères minimum,
        // majuscule + minuscule, au moins un chiffre, au moins un caractère spécial.
        Password::defaults(fn () => Password::min(12)
            ->mixedCase()
            ->letters()
            ->numbers()
            ->symbols());

        // Détecte les en-têtes envoyés par Cloudflare / Localtunnel / Ngrok
        if ($forwardedHost = request()->header('x-forwarded-host')) {
            // Récupère uniquement le premier hôte si une liste séparée par des virgules est envoyée
            $host = trim(explode(',', $forwardedHost)[0]);
            $proto = request()->header('x-forwarded-proto', 'https');

            URL::forceRootUrl("{$proto}://{$host}");
            URL::forceScheme('https');
        }
    }
}
