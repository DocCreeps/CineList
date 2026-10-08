<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de durcissement, appliqués à toutes les réponses HTTP : protections standards
 * (nosniff, anti-iframe, referrer, permissions) et une Content-Security-Policy adaptée à
 * l'application (voir contentSecurityPolicy()).
 */
class SetSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Doit être appelé AVANT le rendu de la vue : Vite et Livewire ajoutent alors ce nonce à
        // leurs balises <script> inline, ce qui les autorise dans la politique ci-dessous.
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Pas de CSP quand le serveur de développement Vite tourne (`npm run dev`) : il sert les
        // assets depuis un autre hôte (et en WebSocket), que la politique bloquerait.
        if (! Vite::isRunningHot()) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));
        }

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Politique adaptée aux dépendances réelles de l'application :
     *  - scripts : le site lui-même + le nonce (scripts inline de Vite/Livewire). Pas de `'unsafe-eval'` :
     *    Livewire embarque la build « CSP » d'Alpine (config/livewire.php, `csp_safe`), qui analyse les
     *    expressions des attributs `x-data`, `x-on:click`… sans jamais les exécuter comme du code ;
     *  - styles : `'unsafe-inline'` (balise <style> de la modale, attributs `style` d'Alpine) + feuille
     *    Google Fonts ; polices servies par fonts.gstatic.com ;
     *  - images : affiches et photos TMDB (image.tmdb.org), logo de attribution TMDB du pied de page
     *    (www.themoviedb.org, exigé par leurs conditions d'utilisation) ; `data:` pour les images intégrées ;
     *  - iframes : bandes-annonces YouTube uniquement ;
     *  - `frame-ancestors 'none'` double X-Frame-Options ; `base-uri` et `form-action` limités au site.
     * Le reste (connexions XHR/WebSocket, objets, médias…) retombe sur `default-src 'self'`.
     */
    private function contentSecurityPolicy(string $nonce): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https://image.tmdb.org https://www.themoviedb.org",
            "frame-src https://www.youtube.com https://www.youtube-nocookie.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }
}
