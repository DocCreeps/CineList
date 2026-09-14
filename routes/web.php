<?php

use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('/connexion', 'auth.login')->name('login');
    Route::livewire('/inscription', 'auth.register')->name('register');
    Route::livewire('/mot-de-passe-oublie', 'auth.forgot-password')->name('password.request');
    Route::livewire('/reinitialiser-mot-de-passe/{token}', 'auth.reset-password')->name('password.reset');

    // Après une connexion réussie sur un compte protégé par la 2FA (session
    // "login.id" présente), avant l'authentification définitive.
    Route::livewire('/deux-facteurs/verification', 'auth.two-factor-challenge')->name('two-factor.login');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/verifier-email', 'auth.verify-email')->name('verification.notice');

    Route::get('/email/verifier/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('home');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::livewire('/confirmer-mot-de-passe', 'auth.confirm-password')->name('password.confirm');

    Route::post('/deconnexion', LogoutController::class)->name('logout');

    // N'exige `verified` en plus de `auth` que si REQUIRE_EMAIL_VERIFICATION=true
    // (voir config/auth.php) — à activer une fois un vrai mailer configuré.
    // Calculé ici plutôt que dans une variable externe : une closure PHP ne
    // capture pas automatiquement les variables du scope englobant.
    Route::middleware(array_filter(['auth', config('auth.require_verified_email') ? 'verified' : null]))->group(function () {
        Route::livewire('/', 'home')->name('home');
        Route::livewire('/tableau-de-bord', 'watchlist.dashboard')->name('watchlist.dashboard');
        Route::livewire('/recherche', 'search.index')->name('search.index');
        Route::livewire('/a-venir', 'upcoming.index')->name('upcoming.index');
        Route::livewire('/statistiques', 'stats.index')->name('stats.index');
    });

    // Pages sensibles : ré-authentification par mot de passe exigée
    // (redirigées vers password.confirm si non confirmées récemment).
    Route::middleware('password.confirm')->prefix('parametres')->name('settings.')->group(function () {
        Route::livewire('/profil', 'settings.profile')->name('profile');
        Route::livewire('/mot-de-passe', 'settings.password')->name('password');
        Route::livewire('/double-authentification', 'settings.two-factor')->name('two-factor');
        Route::livewire('/sessions', 'settings.sessions')->name('sessions');
    });

    // Réservé aux comptes administrateurs (voir App\Http\Middleware\EnsureUserIsAdmin) :
    // confirmation de mot de passe exigée en plus, comme pour les autres pages sensibles.
    Route::middleware(['password.confirm', 'admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/invitations', 'admin.invitations')->name('invitations');
        Route::livewire('/membres', 'admin.members')->name('members');
    });
});
