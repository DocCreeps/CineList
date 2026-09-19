{{--
    Bandeau "Trop de tentatives" avec compte à rebours en direct.

    A placer DANS un élément qui porte x-data="throttleCountdown" (en général le <form>) ; il lit
    `remaining` et `label` de ce composant (voir resources/js/app.js). Invisible tant qu'aucune
    limite n'a été atteinte. Sur le bouton d'envoi du même formulaire, ajouter :

        :disabled="remaining > 0" class="... disabled:pointer-events-none disabled:opacity-50"

    Le décompte est masqué aux lecteurs d'écran (aria-hidden) pour qu'ils n'annoncent pas
    chaque seconde ; ils reçoivent une seule phrase, à l'apparition du bandeau.
--}}
<div
    x-show="remaining > 0"
    x-cloak
    x-transition.opacity.duration.150ms
    role="alert"
    style="display: none;"
    {{ $attributes->class('rounded-xl border border-red-800/40 bg-red-950/30 px-3.5 py-2.5 text-sm text-red-300') }}
>
    <span>Trop de tentatives.</span>
    <span aria-hidden="true">Réessayez dans <strong class="font-bold tabular-nums" x-text="label"></strong>.</span>
    <span class="sr-only">Veuillez patienter avant de réessayer.</span>
</div>
