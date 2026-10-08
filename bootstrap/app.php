<?php

use App\Http\Middleware\DenyDemoUser;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SetSecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SetSecurityHeaders::class);

        // Derrière un proxy inversé (Nginx, Caddy, Cloudflare…), l'IP vue par PHP est celle du proxy :
        // toutes les limites « par IP » (connexion, inscription) partageraient alors un seul compteur.
        // TRUSTED_PROXIES = liste d'IP/CIDR séparés par des virgules, ou `*` pour faire confiance à
        // tout proxy en amont (à n'utiliser que si le serveur n'est joignable QUE par ce proxy).
        // Vide = aucun proxy de confiance (accès direct, comportement par défaut).
        if ($proxies = trim((string) env('TRUSTED_PROXIES', ''))) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        // Invalide les sessions d'un compte dont le mot de passe a changé : c'est ce qui rend
        // `Auth::logoutOtherDevices()` réellement efficace, quel que soit le pilote de session.
        $middleware->web(append: [AuthenticateSession::class]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'not-demo' => DenyDemoUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
