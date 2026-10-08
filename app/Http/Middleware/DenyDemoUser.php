<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interdit au compte démo les pages qui modifient le compte (profil, mot de passe, 2FA,
 * sessions, suppression du compte) ainsi que l'administration.
 */
class DenyDemoUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isDemo()) {
            return redirect()->route('home')->with([
                'notice' => 'Cette page n\'est pas disponible avec le compte démo.',
                'notice_type' => 'info',
            ]);
        }

        return $next($request);
    }
}
