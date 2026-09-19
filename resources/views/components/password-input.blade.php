@props(['tone' => 'amber'])

@php
    // Classes écrites en toutes lettres pour que Tailwind les détecte.
    $focus = $tone === 'red'
        ? 'focus:border-red-500 focus:ring-red-500'
        : 'focus:border-amber-500 focus:ring-amber-500';
@endphp

{{--
    Champ mot de passe avec bouton "afficher / masquer" (Alpine.js, aucune requête réseau).

    Tous les attributs passés au composant (wire:model, id, autocomplete, required, autofocus...)
    sont transmis tels quels au <input> ; seul le prop "tone" ("amber" par défaut, ou "red" pour
    les zones de danger) change la couleur du focus.

    Usage :
        <x-password-input wire:model="password" id="password" autocomplete="current-password" required />

    Chaque champ a son propre état : afficher "Mot de passe" ne dévoile pas "Confirmer".
    Livewire conserve l'état Alpine (x-bind:type) quand il re-rend le composant, donc le champ
    reste visible ou masqué après un envoi.
--}}
<div x-data="{ show: false }" class="relative">
    <input
        {{ $attributes->merge([
            'type' => 'password',
            'class' => "w-full rounded-xl border border-zinc-700 bg-zinc-950 py-2.5 pl-3.5 pr-11 text-sm text-zinc-100 focus:outline-none focus:ring-1 {$focus}",
        ]) }}
        x-bind:type="show ? 'text' : 'password'"
    >

    {{-- mousedown.prevent : cliquer sur l'œil ne retire pas le focus du champ en cours de saisie. --}}
    <button
        type="button"
        x-on:click="show = ! show"
        x-on:mousedown.prevent
        class="absolute inset-y-0 right-0 flex items-center rounded-r-xl px-3.5 text-zinc-500 transition hover:text-zinc-300 focus:outline-none focus-visible:text-amber-400"
        aria-label="Afficher le mot de passe"
        x-bind:aria-label="show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
        x-bind:aria-pressed="show"
    >
        {{-- Œil ouvert : le mot de passe est masqué, cliquer le révèle. --}}
        <svg x-show="! show" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        {{-- Œil barré : le mot de passe est visible, cliquer le masque. --}}
        <svg x-show="show" x-cloak class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
        </svg>
    </button>
</div>
