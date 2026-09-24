<?php

namespace App\Support\Tmdb;

use Illuminate\Support\Str;

/**
 * Noms de pays et de langues en français, via l'extension intl. Extrait de TmdbClient : ces deux
 * fonctions n'ont besoin d'aucun appel réseau ni de cache — pur formatage — et sont testables
 * isolément sans mocker de client HTTP.
 */
class FrenchLocale
{
    /** Nom d'un pays en français (« États-Unis ») ; repli sur le nom fourni par TMDB. */
    public static function regionName(string $isoCode, string $fallback): string
    {
        if ($isoCode !== '' && class_exists(\Locale::class)) {
            $name = \Locale::getDisplayRegion('-'.$isoCode, 'fr');

            if ($name !== '' && $name !== '-'.$isoCode && strcasecmp($name, $isoCode) !== 0) {
                return $name;
            }
        }

        return $fallback !== '' ? $fallback : $isoCode;
    }

    /**
     * Nom de la langue originale en français (« Anglais ») ; repli sur le nom anglais de la liste
     * `spoken_languages` de TMDB, puis sur le code ISO en majuscules.
     *
     * @param  array<int, array<string, mixed>>  $spokenLanguages
     */
    public static function languageName(?string $code, array $spokenLanguages): ?string
    {
        if (blank($code)) {
            return null;
        }

        if (class_exists(\Locale::class)) {
            $name = \Locale::getDisplayLanguage($code, 'fr');

            if ($name !== '' && strcasecmp($name, $code) !== 0) {
                return Str::ucfirst($name);
            }
        }

        $spoken = collect($spokenLanguages)->firstWhere('iso_639_1', $code);

        return $spoken['english_name'] ?? $spoken['name'] ?? strtoupper($code);
    }
}
