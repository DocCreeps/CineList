<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Demo\CreateDemoSandbox;
use App\Actions\Demo\PurgeDemoSandboxes;
use App\Models\DemoLink;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Connexion automatique à la démo via un lien créé dans l'admin (/demo/{token}) : chaque visite
 * ouvre un compte fictif jetable (copie du compte modèle) sur lequel tout est permis. Réponse
 * 404 identique pour un jeton inconnu, révoqué ou expiré, afin de ne rien révéler sur les liens.
 */
class DemoLoginController
{
    public function __invoke(Request $request, string $token, CreateDemoSandbox $createSandbox, PurgeDemoSandboxes $purge): RedirectResponse
    {
        abort_unless(config('demo.enabled'), 404);

        $link = DemoLink::query()->where('token', $token)->first();
        abort_unless($link?->isActive(), 404);

        $template = User::query()->demoTemplate()->first();
        abort_unless($template, 404);

        // Purge opportuniste : la démo reste propre même si le scheduler n'est pas configuré.
        $purge->handle();

        abort_if(
            User::query()->demoSandboxes()->count() >= (int) config('demo.max_sandboxes', 200),
            503,
            'La démo est momentanément saturée, réessayez dans quelques minutes.'
        );

        $sandbox = $createSandbox->handle($template);
        $link->recordUse();

        // Pas de « se souvenir de moi » : la session démo expire normalement.
        Auth::guard('web')->login($sandbox);
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}
