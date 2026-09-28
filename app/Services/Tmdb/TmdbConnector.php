<?php

namespace App\Services\Tmdb;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Client HTTP de base pour TMDB : URL, authentification (v3 en paramètre d'URL ou v4 en Bearer) et
 * disponibilité de la configuration. Partagé par les autres collaborateurs de TmdbClient (recherche,
 * calendrier des sorties, fiches), y compris pour les requêtes envoyées via Http::pool() — d'où le
 * fait qu'authorize() et withAuth() soient publiques : un pool construit ses propres requêtes plutôt
 * que de passer par client().
 */
class TmdbConnector
{
    public function configured(): bool
    {
        return filled(config('services.tmdb.token'));
    }

    public function baseUrl(): string
    {
        return (string) config('services.tmdb.url');
    }

    /**
     * Client HTTP de base pour TMDB, pointé sur l'URL configurée, avec le jeton Bearer v4
     * attaché quand l'identifiant configuré en est un.
     */
    public function client(): PendingRequest
    {
        return $this->authorize(Http::baseUrl($this->baseUrl())->acceptJson());
    }

    /**
     * Ajoute l'authentification Bearer à une requête, mais uniquement quand l'identifiant
     * configuré est un "API Read Access Token" v4 (un JWT). Une clé d'API v3 classique n'est pas
     * un Bearer valide et doit être passée en paramètre d'URL à la place (voir withAuth()).
     */
    public function authorize(PendingRequest $request): PendingRequest
    {
        $token = (string) config('services.tmdb.token');

        return $this->isV4Token($token) ? $request->withToken($token) : $request;
    }

    /**
     * Ajoute l'identifiant TMDB à la chaîne de requête quand une clé d'API v3 est configurée.
     * Les jetons Bearer v4 passent par un en-tête (voir authorize()) : dans ce cas, rien n'est
     * ajouté à l'URL.
     */
    public function withAuth(array $query): array
    {
        $token = (string) config('services.tmdb.token');

        return $this->isV4Token($token) ? $query : [...$query, 'api_key' => $token];
    }

    /**
     * L'"API Read Access Token" v4 de TMDB est un JWT (trois segments séparés par des points).
     * L'ancienne clé d'API v3 est une simple chaîne de 32 caractères et ne doit jamais être
     * envoyée comme jeton Bearer — TMDB la rejette avec une 401.
     */
    private function isV4Token(string $token): bool
    {
        return substr_count($token, '.') === 2;
    }
}
