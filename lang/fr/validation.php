<?php

// Traductions couvrant les règles de validation effectivement utilisées par l'application
// (voir les Actions Fortify, les composants Livewire d'authentification et d'administration).
// À compléter si de nouvelles règles sont utilisées ailleurs.

return [

    'required' => 'Le champ :attribute est obligatoire.',
    'required_unless' => 'Le champ :attribute est obligatoire, sauf si :other vaut :values.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'unique' => 'Cette valeur du champ :attribute est déjà utilisée.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'nullable' => 'Le champ :attribute est invalide.',

    'max' => [
        'numeric' => 'Le champ :attribute ne doit pas être supérieur à :max.',
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
        'array' => 'Le champ :attribute ne doit pas comporter plus de :max éléments.',
    ],

    'min' => [
        'numeric' => 'Le champ :attribute doit être au moins :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
        'array' => 'Le champ :attribute doit comporter au moins :min éléments.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Règles de mot de passe (Illuminate\Validation\Rules\Password)
    |--------------------------------------------------------------------------
    |
    | Utilisées via Password::defaults() dans AppServiceProvider (12 caractères
    | minimum, majuscule + minuscule, chiffre, caractère spécial).
    |
    */

    'password' => [
        'letters' => 'Le champ :attribute doit contenir au moins une lettre.',
        'mixed' => 'Le champ :attribute doit contenir au moins une majuscule et une minuscule.',
        'numbers' => 'Le champ :attribute doit contenir au moins un chiffre.',
        'symbols' => 'Le champ :attribute doit contenir au moins un caractère spécial.',
        'uncompromised' => 'Le champ :attribute donné a été exposé lors d\'une fuite de données. Merci de choisir une autre valeur pour :attribute.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Noms des champs
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'nom',
        'email' => 'e-mail',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'current_password' => 'mot de passe actuel',
        'invite_code' => 'code d\'invitation',
        'code' => 'code',
        'recovery_code' => 'code de récupération',
        'expiresInDays' => 'délai d\'expiration',
        'maxUses' => 'nombre d\'utilisations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Messages personnalisés
    |--------------------------------------------------------------------------
    |
    | Les messages spécifiques à un champ précis (ex. throttle de connexion,
    | code d'invitation invalide) sont déjà écrits en français directement
    | dans le code applicatif et n'ont pas besoin d'entrée ici.
    |
    */

    'custom' => [],

];
